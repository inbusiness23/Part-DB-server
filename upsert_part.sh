   #!/bin/bash
   export $(cat .env.local | grep PARTDB_API_TOKEN)

   echo "Creating new part..."
   curl -v -X POST "http://localhost:8000/api/parts" \
        -H "Authorization: Bearer $PARTDB_API_TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{"name":"4ft x 50ft Galvanized Chainlink Roll","description":"4-foot high residential galvanized chainlink fence roll","amount":10,"category":"/api/categories/5"}'

