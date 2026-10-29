const ACCOUNTS_KEY = 'opark-demo-accounts'
const SESSION_KEY = 'opark-demo-session'
const REMEMBERED_USER_KEY = 'opark-demo-remembered-user'
const PBKDF2_ITERATIONS = 120000
const platePattern = /^[A-Z0-9](?:[A-Z0-9 -]{0,18}[A-Z0-9])?$/

const panels = {
  login: document.getElementById('login-panel'),
  signup: document.getElementById('signup-panel'),
}

function getAccounts() {
  const savedAccounts = localStorage.getItem(ACCOUNTS_KEY)
  if (savedAccounts === null) return []

  const accounts = JSON.parse(savedAccounts)
  if (
    !Array.isArray(accounts) ||
    accounts.some(
      (account) =>
        !account ||
        typeof account.name !== 'string' ||
        typeof account.email !== 'string' ||
        typeof account.plate !== 'string' ||
        typeof account.role !== 'string' ||
        typeof account.salt !== 'string' ||
        !/^(?:[0-9a-f]{2}){16}$/i.test(account.salt) ||
        typeof account.passwordHash !== 'string' ||
        !/^[0-9a-f]{64}$/i.test(account.passwordHash),
    )
  ) {
    throw new Error('Saved demo accounts are unreadable. Clear this site’s data and try again.')
  }
  return accounts
}

function saveAccounts(accounts) {
  localStorage.setItem(ACCOUNTS_KEY, JSON.stringify(accounts))
}

function bytesToHex(bytes) {
  return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('')
}

async function hashPassword(password, salt) {
  if (!globalThis.crypto?.subtle) {
    throw new Error('Password hashing is unavailable. Open this site over HTTPS and try again.')
  }

  const key = await crypto.subtle.importKey(
    'raw',
    new TextEncoder().encode(password),
    'PBKDF2',
    false,
    ['deriveBits'],
  )
  const hash = await crypto.subtle.deriveBits(
    { name: 'PBKDF2', salt, iterations: PBKDF2_ITERATIONS, hash: 'SHA-256' },
    key,
    256,
  )
  return bytesToHex(new Uint8Array(hash))
}

function showPanel(name) {
  Object.entries(panels).forEach(([panelName, panel]) => {
    panel.hidden = panelName !== name
  })

  document.getElementById('page-title').textContent =
    name === 'login' ? 'Welcome Back' : 'Create an Account'
}

function showError(formName, message) {
  const error = document.getElementById(`${formName}-error`)
  error.textContent = message
  error.hidden = false
}

function clearError(formName) {
  const error = document.getElementById(`${formName}-error`)
  error.textContent = ''
  error.hidden = true
}

function normalizedPlate(value) {
  const plate = value.trim().replace(/\s+/g, ' ').toUpperCase()
  return platePattern.test(plate) ? plate : null
}

function startSession(account, remember) {
  sessionStorage.setItem(SESSION_KEY, account.email)
  if (remember) {
    localStorage.setItem(REMEMBERED_USER_KEY, account.email)
  } else {
    localStorage.removeItem(REMEMBERED_USER_KEY)
  }
  window.location.assign('./dashboard.html')
}

document.querySelectorAll('[data-show]').forEach((link) => {
  link.addEventListener('click', () => {
    clearError('login')
    clearError('signup')
    showPanel(link.dataset.show)
  })
})

document.getElementById('signup-form').addEventListener('submit', async (event) => {
  event.preventDefault()
  clearError('signup')

  const submitButton = event.currentTarget.querySelector('button[type="submit"]')
  submitButton.disabled = true

  try {
    const name = document.getElementById('signup-name').value.trim()
    const email = document.getElementById('signup-email').value.trim().toLowerCase()
    const plate = normalizedPlate(document.getElementById('signup-plate').value)
    const password = document.getElementById('signup-password').value
    const role = document.getElementById('signup-role').value

    if (!plate) {
      throw new Error('Enter a valid plate number using letters, numbers, spaces, or hyphens.')
    }

    const accounts = getAccounts()
    if (accounts.some((account) => account.email === email)) {
      throw new Error('That email already has a demo account in this browser.')
    }

    const salt = crypto.getRandomValues(new Uint8Array(16))
    const account = {
      name,
      email,
      plate,
      role,
      salt: bytesToHex(salt),
      passwordHash: await hashPassword(password, salt),
    }
    accounts.push(account)
    saveAccounts(accounts)
    startSession(account, false)
  } catch (error) {
    showError('signup', error instanceof Error ? error.message : 'Could not create the demo account.')
  } finally {
    submitButton.disabled = false
  }
})

document.getElementById('login-form').addEventListener('submit', async (event) => {
  event.preventDefault()
  clearError('login')

  const submitButton = event.currentTarget.querySelector('button[type="submit"]')
  submitButton.disabled = true

  try {
    const email = document.getElementById('login-email').value.trim().toLowerCase()
    const password = document.getElementById('login-password').value
    const plate = normalizedPlate(document.getElementById('login-plate').value)
    if (!plate) {
      throw new Error('Enter a valid plate number using letters, numbers, spaces, or hyphens.')
    }

    const accounts = getAccounts()
    const account = accounts.find((savedAccount) => savedAccount.email === email)
    const storedSalt = account
      ? Uint8Array.from(account.salt.match(/.{2}/g), (byte) => Number.parseInt(byte, 16))
      : new Uint8Array(16)
    if (!account || await hashPassword(password, storedSalt) !== account.passwordHash) {
      throw new Error('Email or password is incorrect. Create a demo account if you have not signed up here.')
    }

    account.plate = plate
    saveAccounts(accounts)
    startSession(account, document.getElementById('remember-me').checked)
  } catch (error) {
    showError('login', error instanceof Error ? error.message : 'Could not sign in to the demo account.')
  } finally {
    submitButton.disabled = false
  }
})

try {
  const accounts = getAccounts()
  const email = sessionStorage.getItem(SESSION_KEY) || localStorage.getItem(REMEMBERED_USER_KEY)
  const account = accounts.find((savedAccount) => savedAccount.email === email)
  if (account) {
    window.location.replace('./dashboard.html')
  } else {
    sessionStorage.removeItem(SESSION_KEY)
    localStorage.removeItem(REMEMBERED_USER_KEY)
    showPanel('login')
  }
} catch (error) {
  showPanel('login')
  showError('login', error instanceof Error ? error.message : 'Could not load demo accounts.')
}
