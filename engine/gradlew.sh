#!/bin/bash
export JAVA_HOME=$HOME/.tools/jdk21
export PATH=$JAVA_HOME/bin:$PATH
cd "$(dirname "$0")"
exec ~/.tools/gradle/bin/gradle "$@"
