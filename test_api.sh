   #!/bin/bash
   # Load token
   export $(cat .env.local | grep PARTDB_API_TOKEN)

   echo "1. Testing API connection..."
   echo "Getting parts list:"
   curl -v "http://localhost:8000/api/parts" \
        -H "Authorization: Bearer $PARTDB_API_TOKEN" \
        -H "Accept: application/json"

   echo "\n\n2. Getting categories:"
   curl -v "http://localhost:8000/api/categories" \
        -H "Authorization: Bearer $PARTDB_API_TOKEN" \
        -H "Accept: application/json"

