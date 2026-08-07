#!/bin/bash
PORT=${PORT:-8091}
java -jar -Dspring.profiles.active=prod server/kicc-engine.jar \
  --server.port=$PORT \
  --kicc.web-dir=web \
  --spring.datasource.url="jdbc:mysql://gateway01.eu-central-1.prod.aws.tidbcloud.com:4000/kicc?useSSL=true&requireSSL=true&serverTimezone=UTC&allowPublicKeyRetrieval=true"
echo "KICC Engine started on port $PORT"
