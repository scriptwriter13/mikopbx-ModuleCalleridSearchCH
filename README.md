# About
This is a Module for Mikopbx, which makes search.ch API (Swiss Phonebook) accessable for Callerid.

Module was built by scriptwriter13 in 2026 and released by GPL V3.
It is designed for usage with the mikopbx sip-telephony-service.

# Install

Go to https://tel.search.ch and register your API-key. If you dont use this API hard, you can get this for free.

Go to your System (possibly docker-compose.yml or wherever you can set your environment) and add this API-Key to your 
```
    environment:
      - TEL_SEARCH_KEY=[yourtelsearchapikey]
```

Restart your Mikopbx for getting this new environment

Go to Modules → Marketplace in MikoPBX admin panel
Find CalleridSearchCH.zip module
Click Install

# Build

If you got the files from Github and not the released CalleridSearchCH.zip then go to directory with the content and type
`zip -r ../CalleridSearchCH.zip .`
After that you can follow the Install-steps.
