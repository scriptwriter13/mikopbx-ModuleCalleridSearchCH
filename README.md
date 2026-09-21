# About
This is a module for MikoPBX which shows the caller name from the Swiss phone directory
[tel.search.ch](https://tel.search.ch) on incoming calls.

On every incoming call the number is looked up in the directory and the CallerID name is set to
`Name F. City, Street 1`. Numbers may arrive as `044…`, `+4144…`, `004144…` or `4144…`.
Numbers outside Switzerland and numbers missing from the directory are left untouched,
so a name sent by your provider is not lost.

Module was built by scriptwriter13 in 2026 and released by GPL V3.
[Русская версия](README.ru.md)

# Install

**Upgrading from 1.0:** remove the old `CalleridSearchCH` module first. The module id gained the
`Module` prefix required by MikoPBX, so an install over 1.0 leaves both modules enabled, with two
lookups on every incoming call and the old script still running.

1. Build the module archive (see below) or take it from the releases page.
2. In the MikoPBX admin panel open Modules → Installed → Upload new module and choose the zip file.
3. Enable the module.
4. Open the module page, paste your API key and press Save.

Requires MikoPBX 2025.1.1 or newer.

# API key

Get a key at https://tel.search.ch/api/getkey. It is free for moderate use.
Without a key the directory returns the name only, without city and street.

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
