#!/usr/bin/php
<?php
/*                                                                                                                                        
 * Copyright (C) 2026 by scriptwriter13                                                                                       
 *                                                                                                                                        
 * Dieses Programm ist freie Software: Sie können es unter den Bedingungen der                                                            
 * GNU General Public License, wie von der Free Software Foundation veröffentlicht,                                                       
 * entweder Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren                                                                  
 * Version, weiterverbreiten und/oder modifizieren.                                                                                       
 *                                                                                                                                        
 * Dieses Programm wird in der Hoffnung, dass es nützlich sein wird, aber                                                                 
 * OHNE JEDE GEWÄHRLEISTUNG, sogar ohne die implizite Gewährleistung der                                                                  
 * MARKTGÄNGIGKEIT oder EIGNUNG FÜR EINEN BESTIMMTEN ZWECK. Siehe die                                                                     
 * GNU General Public License für weitere Details.                                                                                        
 *                                                                                                                                        
 * Sie sollten eine Kopie der GNU General Public License zusammen mit diesem                                                              
 * Programm erhalten haben. Wenn nicht, siehe <https://www.gnu.org/licenses/>.                                                            
 */

// MikoPBX AGI Lookup Skript (mit Strasse und Hausnummer)
set_time_limit(5); // Timeout für die API
$number = $argv[1] ?? '';

// --- CONFIG ---
$debug_mode = true; // Auf false setzen für den reinen Produktionsbetrieb

// Hilfsfunktion für Debug-Ausgaben (schreibt in stderr und in die permanente Logdatei)
function debug_log($message) {
    global $debug_mode;
    if ($debug_mode) {
        $logMessage = "[AGI-DEBUG] " . $message . "\n";
        @file_put_contents('php://stderr', $logMessage);
        
        $logFile = '/storage/usbdisk1/mikopbx/custom_modules/CalleridSearchCH/debug.log';
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[$timestamp] " . $logMessage, FILE_APPEND);
    }
}

debug_log("Skript gestartet für Nummer: " . $number);

// API-Key aus Env holen, mit direktem Fallback
$tel_key = getenv('TEL_SEARCH_KEY');
if (empty($tel_key)) {
    debug_log("WARNUNG: TEL_SEARCH_KEY nicht in ENV gefunden. Nutze Fallback-Key.");
    $tel_key = "DEIN_ECHTER_API_KEY_HIER_EINTRAGEN"; 
} else {
    // Key sicher maskieren (nur erster und letzter Teil sichtbar)
    $masked_key = substr($tel_key, 0, 4) . '****' . substr($tel_key, -4);
    debug_log("API-Key erfolgreich aus Umgebungsvariable geladen (Wert: " . $masked_key . ").");
}

function send_agi($command) {
    echo $command . "\n";
    $response = fgets(STDIN); // Liest die Antwort von Asterisk
    return $response;
}

$name = $number; // Default

// 1. Schweiz-Logik (tel.search.ch)
if (preg_match('/^0[1-9]/', $number)) {
    $url = "https://tel.search.ch/api/?was=" . urlencode($number) . "&lang=de&key=" . urlencode($tel_key);
    
    // URL fürs Loggen anonymisieren
    $safe_url = preg_replace('/key=[^&]+/', 'key=REDACTED', $url);
    debug_log("Rufe URL auf: " . $safe_url);
    
    $xml = @file_get_contents($url);
    
    if ($xml === false) {
        debug_log("FEHLER: file_get_contents konnte keine Verbindung zur API herstellen.");
    } else {
        debug_log("XML erfolgreich empfangen (Länge: " . strlen($xml) . " Bytes).");
        
        $dom = new DOMDocument();
        @$dom->loadXML($xml);
        
        $xpath = new DOMXPath($dom);
        $entries = $xpath->query('//*[local-name()="entry"]');
        
        debug_log("Anzahl gefundener <entry>-Einträge im XML: " . $entries->length);
        
        if ($entries->length > 0) {
            $names_list = [];
            $global_city = '';
            $global_street_full = '';
            $has_multiple = ($entries->length > 1);
            
            foreach ($entries as $index => $entry) {
                $n_node       = $xpath->query('./*[local-name()="name" and namespace-uri()="http://tel.search.ch/api/spec/result/1.0/"]', $entry)->item(0);
                if (!$n_node) {
                    $n_node = $xpath->query('./*[local-name()="name" and not(parent::*[local-name()="author"])]', $entry)->item(0);
                }

                $fn_node      = $xpath->query('.//*[local-name()="firstname"]', $entry)->item(0);
                $street_node  = $xpath->query('.//*[local-name()="street"]', $entry)->item(0);
                $streetno_node= $xpath->query('.//*[local-name()="streetno"]', $entry)->item(0);
                $city_node    = $xpath->query('.//*[local-name()="city"]', $entry)->item(0);

                $nachname     = $n_node ? trim($n_node->nodeValue) : '';
                $vorname      = $fn_node ? trim($fn_node->nodeValue) : '';
                $strasse      = $street_node ? trim($street_node->nodeValue) : '';
                $hausnummer   = $streetno_node ? trim($streetno_node->nodeValue) : '';
                
                if (empty($global_city) && $city_node) {
                    $global_city = trim($city_node->nodeValue);
                }
                
                // Strasse und Hausnummer zusammenführen (z. B. "Mittelweg 13")
                if (empty($global_street_full) && ($strasse !== '' || $hausnummer !== '')) {
                    $global_street_full = trim($strasse . ' ' . $hausnummer);
                }

                debug_log("Eintrag #{$index}: Nachname='{$nachname}', Vorname='{$vorname}', Adresse='{$global_street_full}', Ort='{$global_city}'");

                $person_parts = [];
                if ($nachname !== '') {
                    $person_parts[] = $nachname;
                }

                // Vornamen nur bei einem einzelnen Eintrag abkürzen und hinzufügen; bei mehreren Namen weglassen
                if (!$has_multiple && $vorname !== '') {
                    $vorname = mb_substr($vorname, 0, 1) . '.';
                    $person_parts[] = $vorname;
                }

                if (!empty($person_parts)) {
                    $names_list[] = implode(' ', $person_parts);
                }
            }
            
            // Zusammenbauen im Format: Name City, Street Number (ohne Leerzeichen um den Slash)
            if (!empty($names_list)) {
                $name = implode('/', $names_list);
                if ($global_city !== '') {
                    $name .= ' ' . $global_city;
                }
                if ($global_street_full !== '') {
                    $name .= ', ' . $global_street_full;
                }
            }
        } else {
            debug_log("Keine passenden Einträge für diese Nummer gefunden.");
        }
    }
} else {
    debug_log("Nummer entspricht nicht dem Schweizer Schema (^0[1-9]). Überspringe API-Abfrage.");
}

debug_log("Finaler CallerID Name: " . $name);

// 2. Setze CallerID (mit doppelten Anführungszeichen für saubere AGI-Übertragung)
$safe_name = str_replace('"', '\"', $name);
send_agi("SET VARIABLE CALLERID(name) \"" . $safe_name . "\"");

// 3. Call Handling (Call Center / Anonym / etc.)
if (stripos($name, 'Call Center') !== false || $number == "00000000" || strpos($name, '6000') !== false) {
    debug_log("Spezial-Call-Handling greift (Call Center / Anonym).");
    send_agi("ANSWER");
    send_agi("WAIT 1");
    send_agi("PLAYBACK " . (stripos($name, 'Call Center') !== false ? "callcenter" : "anonym"));
    send_agi("HANGUP");
}
?>
