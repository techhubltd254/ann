package ke.go.kicc.engine.notify

import org.slf4j.LoggerFactory
import org.springframework.stereotype.Service

@Service
class SmsService {
    private val log = LoggerFactory.getLogger(javaClass)

    fun send(phone: String, message: String) {
        log.info("SMS stub → $phone: ${message.take(100)}")
    }
}
