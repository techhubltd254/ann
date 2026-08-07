package ke.go.kicc.engine

import org.springframework.boot.autoconfigure.SpringBootApplication
import org.springframework.boot.runApplication
import org.springframework.scheduling.annotation.EnableScheduling

@SpringBootApplication
@EnableScheduling
class EngineApplication

fun main(args: Array<String>) {
    runApplication<EngineApplication>(*args)
}
