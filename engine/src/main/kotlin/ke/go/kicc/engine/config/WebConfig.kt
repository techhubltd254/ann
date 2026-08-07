package ke.go.kicc.engine.config

import org.springframework.beans.factory.annotation.Value
import org.springframework.context.annotation.Configuration
import org.springframework.core.io.Resource
import org.springframework.web.servlet.config.annotation.ResourceHandlerRegistry
import org.springframework.web.servlet.config.annotation.ViewControllerRegistry
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer
import org.springframework.web.servlet.resource.PathResourceResolver
import java.io.File

@Configuration
class WebConfig(
    @Value("\${kicc.web-dir:../web}") private val webDir: String
) : WebMvcConfigurer {

    override fun addViewControllers(registry: ViewControllerRegistry) {
        registry.addViewController("/").setViewName("forward:index.html")
    }

    override fun addResourceHandlers(registry: ResourceHandlerRegistry) {
        registry.addResourceHandler("/**")
            .addResourceLocations("file:$webDir/")
            .setCachePeriod(3600)
            .resourceChain(true)
            .addResolver(object : PathResourceResolver() {
                override fun getResource(resourcePath: String, location: Resource): Resource? {
                    val lookup = resourcePath.ifEmpty { "index.html" }
                    val file = File(webDir, lookup)
                    if (file.exists() && file.isFile && file.canRead()) return super.getResource(lookup, location)
                    if (resourcePath.contains(".") || resourcePath.startsWith("api/")) return null
                    return super.getResource("index.html", location)
                }
            })
    }
}


