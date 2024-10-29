   #!/bin/bash
   # Load token
   export $(cat .env.local | grep PARTDB_API_TOKEN)

   # Replace PART_ID with the actual ID of the part you want to update
   PART_ID=1

   echo "Updating part ${PART_ID}..."
   curl -v -X PATCH "http://localhost:8000/api/parts/${PART_ID}" \
        -H "Authorization: Bearer $PARTDB_API_TOKEN" \
        -H "Content-Type: application/merge-patch+json" \
        -H "Accept: application/json" \
        -d '{
          "amount": 75,
          "description": "Updated via API"
        }'
  
