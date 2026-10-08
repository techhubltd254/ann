(function () {
  'use strict'

  // Voice search via Web Speech API (on-device, Chrome/Edge/Safari)
  // Adds a microphone button to all search inputs with [data-voice-search]

  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
  if (!SpeechRecognition) return

  document.querySelectorAll('[data-voice-search]').forEach(function (input) {
    var btn = document.createElement('button')
    btn.type = 'button'
    btn.setAttribute('aria-label', 'Search by voice')
    btn.className = 'voice-search-btn absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-200 cursor-pointer'
    btn.innerHTML = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="22"/></svg>'
    btn.style.cssText = 'background: transparent; color: #5A6480; border: none;'

    var recognition = new SpeechRecognition()
    recognition.lang = 'en'
    recognition.continuous = false
    recognition.interimResults = false
    recognition.maxAlternatives = 1

    var isListening = false

    btn.addEventListener('click', function (e) {
      e.preventDefault()
      if (isListening) {
        recognition.stop()
        return
      }
      isListening = true
      btn.classList.add('is-listening')
      btn.style.background = '#b3261e'
      btn.style.color = 'white'
      btn.innerHTML = '<svg class="w-4 h-4 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="22"/></svg>'
      recognition.start()
    })

    recognition.addEventListener('result', function (e) {
      var transcript = e.results[0][0].transcript
      input.value = transcript
      input.dispatchEvent(new Event('input', { bubbles: true }))
      // Trigger search if the input has an x-model (Alpine.js)
      if (input.getAttribute('x-model')) {
        input.dispatchEvent(new Event('change', { bubbles: true }))
      }
      // Submit parent form if exists
      var form = input.closest('form')
      if (form) form.submit()
    })

    recognition.addEventListener('end', function () {
      isListening = false
      btn.classList.remove('is-listening')
      btn.style.background = 'transparent'
      btn.style.color = '#5A6480'
      btn.innerHTML = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="22"/></svg>'
    })

    recognition.addEventListener('error', function (e) {
      console.warn('voice search error:', e.error)
      isListening = false
      btn.classList.remove('is-listening')
      btn.style.background = 'transparent'
      btn.style.color = '#ef4444'
      btn.innerHTML = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
      setTimeout(function () {
        btn.style.color = '#5A6480'
        btn.innerHTML = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="22"/></svg>'
      }, 2000)
    })

    // Make the input wrapper position relative so the button positions correctly
    var wrapper = input.closest('.relative')
    if (!wrapper) {
      input.style.paddingRight = '2.5rem'
      input.parentNode.style.position = 'relative'
      input.parentNode.appendChild(btn)
    } else {
      wrapper.appendChild(btn)
    }
  })
})()