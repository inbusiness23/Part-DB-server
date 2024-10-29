   #!/bin/bash
   # Load token
   export $(cat .env.local | grep PARTDB_API_TOKEN)

   # Print token (first few characters)
   echo "Using token: ${PARTDB_API_TOKEN:0:10}..."

   # Add -v flag to see what's happening
   curl -v -X POST "http://localhost:8000/api/parts" \
        -H "Authorization: Bearer $PARTDB_API_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{"name": "Test Part", "description": "Simple test part"}'

   echo "\nRequest completed"
   ./test_add.sh


#!/bin/bash
# Load token
export $(cat .env.local | grep PARTDB_API_TOKEN)

# Print token (first few characters)
echo "Using token: ${PARTDB_API_TOKEN:0:10}..."

# Add -v flag to see what's happening
curl -v -X POST "http://localhost:8000/api/parts" \
     -H "Authorization: Bearer $PARTDB_API_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name": "Test Part", "description": "Simple test part"}'

echo "\nRequest completed"

