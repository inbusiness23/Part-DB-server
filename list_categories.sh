   #!/bin/bash
   export $(cat .env.local | grep PARTDB_API_TOKEN)

   echo "Getting categories..."
   curl -v "http://localhost:8000/api/categories" \
        -H "Authorization: Bearer $PARTDB_API_TOKEN" \
        -H "Accept: application/json"

