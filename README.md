# About
This is a module for MikoPBX which shows the caller name from the Swiss phone directory
[tel.search.ch](https://tel.search.ch) on incoming calls.

On every incoming call the number is looked up in the directory and the CallerID name is set to
`Name F. City, Street 1`. Numbers may arrive as `044…`, `+4144…`, `004144…` or `4144…`.
Numbers outside Switzerland and numbers missing from the directory are left untouched,
so a name sent by your provider is not lost.

Module was built by scriptwriter13 in 2026 and released by GPL V3.
[deutsche Version](README.de.md)
[Русская версия](README.ru.md)

# Install

**Upgrading from older version:** remove the old `CalleridSearchCH` module first. The module id gained the
`Module` prefix required by MikoPBX, so an install over the old one leaves both modules enabled, with one 
lookup per enabled module on every incoming call and the old script still running.

1. Build the module archive (see below) or take it from the releases page.
2. In the MikoPBX admin panel open Modules → Installed → Upload new module and choose the zip file.
3. Enable the module.
4. Open the module page, paste your API key and press Save.
5. If desired, you can also modify the call flow. Only make these settings if you are certain of 
what you are doing. Here, it is possible to reject anonymous calls or calls categorized as 
call center traffic. It is also optionally possible to play a custom-recorded announcement before 
the call is dropped.

Requires MikoPBX 2025.1.1 or newer.

## API key

Get a key at https://tel.search.ch/api/getkey. It is free for moderate use.
Without a key the directory returns the name only, without city and street.

## Encoding Mode (CallerID Transliteration)
To ensure full compatibility with various IP and analog telephone models, the module provides a mutually exclusive "Encoding Mode" dropdown in the settings. You can select one of the following options:

* **Standard (UTF-8 / No conversion)** (`none`)  
  * *Description:* Passes characters through unmodified using standard UTF-8 encoding. Best suited for modern IP phones that handle international characters natively.
* **Transliterate Special Characters** (`ascii`)  
  * *Description:* Converts special characters and umlauts (e.g., `ö` -> `oe`, `ä` -> `ae`, `ü` -> `ue`) into standard ASCII equivalents. Ideal for older IP phones that struggle with non-ASCII characters.
* **Force Swiss ISO-646-CH** (`iso646ch`)  
  * *Description:* Enforces the historical Swiss ISO-646-CH character set mapping. Specifically designed for legacy Swiss telephone systems and older analog/digital end devices requiring this standard.
* **ISO-8859-1 (Latin-1)** (`iso88591`)  
  * *Description:* Uses the classic ISO-8859-1 Western European encoding. Recommended for older digital desk phones and legacy PBX hardware.

# Privacy

The caller number of every incoming Swiss call is sent to tel.search.ch over HTTPS.
The module stores nothing about calls and writes no log of names or addresses.

# Troubleshooting

- The directory is asked with a hard 2 second budget for the whole request. If it does not answer
  in time, the call goes on with the original CallerID.
- Failures are never silent and never break a call: the reason is written to the Asterisk log
  through AGI verbose, prefixed `CalleridSearchCH:`. A rejected or exhausted key shows up there.
  Caller names and addresses are not logged.
- No name is shown: check the Asterisk log first, then that the number has a public entry at
  tel.search.ch.
- Run the self-check without PBX and network: `php tests/LookupTest.php`

# Build

`zip -r ../ModuleCalleridSearchCH.zip . -x '.git/*'`
