   #!/bin/bash
   export $(cat .env.local | grep PARTDB_API_TOKEN)

   # Configuration
   DEFAULT_CATEGORY="/api/categories/5"  # Galvanized Chainlink
   PART_NAME="4ft x 50ft Galvanized Chainlink Roll"
   NEW_AMOUNT=25

   echo "Searching for part: $PART_NAME"
   SEARCH_RESULT=$(curl -s "http://localhost:8000/api/parts?name=${PART_NAME}" \
        -H "Authorization: Bearer $PARTDB_API_TOKEN" \
        -H "Accept: application/json")

   if echo "$SEARCH_RESULT" | grep -q "\"hydra:totalItems\":0"; then
     echo "Part not found. Creating new part..."
     curl -X POST "http://localhost:8000/api/parts" \
          -H "Authorization: Bearer $PARTDB_API_TOKEN" \
          -H "Content-Type: application/json" \
          -d "{
            \"name\": \"$PART_NAME\",
            \"description\": \"Auto-created part\",
            \"amount\": $NEW_AMOUNT,
            \"category\": \"$DEFAULT_CATEGORY\",
            \"needs_review\": true
          }"
   else
     echo "Part found. Updating amount..."
     PART_ID=$(echo "$SEARCH_RESULT" | grep -o '\"@id\":\"/api/parts/[0-9]*\"' | grep -o '[0-9]*' | head -1)
     
     curl -X PATCH "http://localhost:8000/api/parts/$PART_ID" \
          -H "Authorization: Bearer $PARTDB_API_TOKEN" \
          -H "Content-Type: application/merge-patch+json" \
          -d "{
            \"amount\": $NEW_AMOUNT
          }"
   fi


