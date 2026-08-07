const { app, BrowserWindow } = require('electron')
const http = require('http')

const PORT = 8091
const URL = `http://localhost:${PORT}`

function waitForServer() {
  return new Promise((resolve) => {
    const check = () => {
      http.get(`${URL}/api/health`, (res) => {
        if (res.statusCode === 200) resolve()
        else setTimeout(check, 500)
      }).on('error', () => setTimeout(check, 500))
    }
    check()
  })
}

function createWindow() {
  const win = new BrowserWindow({
    width: 1280,
    height: 800,
    minWidth: 900,
    minHeight: 600,
    title: 'KICC Platform',
    backgroundColor: '#0f172a',
    show: false,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true
    }
  })
  win.setMenuBarVisibility(false)
  win.loadURL(URL)
  win.once('ready-to-show', () => win.show())
  win.on('closed', () => app.quit())
}

app.whenReady().then(async () => {
  createWindow()
  try { await waitForServer() } catch {}
  if (BrowserWindow.getAllWindows().length > 0) {
    BrowserWindow.getAllWindows()[0].loadURL(URL)
  }
})

app.on('window-all-closed', () => app.quit())
