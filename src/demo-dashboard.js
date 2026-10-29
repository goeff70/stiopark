const ACCOUNTS_KEY = 'opark-demo-accounts'
const SESSION_KEY = 'opark-demo-session'
const REMEMBERED_USER_KEY = 'opark-demo-remembered-user'
const RESERVATIONS_KEY = 'opark-demo-reservations'
const laneList = document.getElementById('lane-list')
const reservationDialog = document.getElementById('reservation-dialog')
const reservationForm = document.getElementById('reservation-form')
const reservationError = document.getElementById('reservation-error')
const message = document.getElementById('dashboard-message')
const validPurposes = new Set(['Enrollment', 'Payment', 'Classes', 'TESDA', 'Other'])
let currentAccount
let selectedLane

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
        typeof account.passwordHash !== 'string',
    )
  ) {
    throw new Error('Saved demo accounts are unreadable. Clear this site’s data and sign in again.')
  }
  return accounts
}

function getReservations() {
  const savedReservations = localStorage.getItem(RESERVATIONS_KEY)
  if (savedReservations === null) return []

  const reservations = JSON.parse(savedReservations)
  if (
    !Array.isArray(reservations) ||
    reservations.some(
      (reservation) =>
        !reservation ||
        typeof reservation.id !== 'string' ||
        typeof reservation.email !== 'string' ||
        !Number.isInteger(reservation.lane) ||
        reservation.lane < 1 ||
        reservation.lane > 5 ||
        typeof reservation.name !== 'string' ||
        typeof reservation.plate !== 'string' ||
        !validPurposes.has(reservation.purpose) ||
        !Number.isFinite(Date.parse(reservation.start)) ||
        !Number.isFinite(Date.parse(reservation.end)) ||
        Date.parse(reservation.start) >= Date.parse(reservation.end),
    )
  ) {
    throw new Error('Saved demo reservations are unreadable. Clear this site’s data and try again.')
  }
  return reservations
}

function saveReservations(reservations) {
  localStorage.setItem(RESERVATIONS_KEY, JSON.stringify(reservations))
}

function showMessage(text, isError = false) {
  message.textContent = text
  message.dataset.error = String(isError)
  message.hidden = false
}

function toLocalDateTimeValue(date) {
  const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000)
  return localDate.toISOString().slice(0, 16)
}

function formatReservationTime(value) {
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

function reservationDetails(reservation) {
  return `${reservation.purpose} · ${formatReservationTime(reservation.start)} – ${formatReservationTime(reservation.end)}`
}

function removeExpiredReservations(reservations, now) {
  const active = reservations.filter((reservation) => Date.parse(reservation.end) > now)
  if (active.length !== reservations.length) saveReservations(active)
  return active
}

function createLaneCard(laneNumber, reservations, now, ownReservation) {
  const card = document.createElement('article')
  card.className = 'lane-card'

  const heading = document.createElement('h3')
  heading.className = 'lane-title'
  heading.textContent = `Lane ${laneNumber}`

  const status = document.createElement('p')
  status.className = 'lane-status'

  const detail = document.createElement('p')
  detail.className = 'lane-details'

  const button = document.createElement('button')
  button.type = 'button'
  button.className = 'form-btn form-btn--submit lane-action'

  const laneReservation = reservations.find((reservation) => reservation.lane === laneNumber)
  if (laneReservation) {
    const isMine = laneReservation.email === currentAccount.email
    const isInProgress = Date.parse(laneReservation.start) <= now
    card.classList.add('lane-card--reserved')
    status.textContent = isMine
      ? isInProgress
        ? 'Your reservation'
        : 'Scheduled by you'
      : isInProgress
        ? 'Reserved'
        : 'Booked later'
    detail.textContent = `${isMine ? 'Your booking' : 'A demo booking on this device'}: ${reservationDetails(laneReservation)}`

    if (isMine) {
      button.textContent = 'Cancel my reservation'
      button.addEventListener('click', () => cancelReservation(laneReservation.id))
    } else {
      button.textContent = isInProgress ? 'Currently reserved' : 'Reserve another time'
      button.disabled = isInProgress || Boolean(ownReservation)
      if (!button.disabled) {
        button.addEventListener('click', () => openReservationDialog(laneNumber))
      }
    }
  } else {
    status.textContent = 'Available'
    detail.textContent = ownReservation
      ? 'Cancel your existing reservation before booking another lane.'
      : 'Choose this lane to make a demo reservation.'
    button.textContent = ownReservation ? 'One reservation at a time' : 'Reserve this lane'
    button.disabled = Boolean(ownReservation)
    if (!button.disabled) {
      button.addEventListener('click', () => openReservationDialog(laneNumber))
    }
  }

  card.append(heading, status, detail, button)
  return card
}

function renderLanes() {
  const now = Date.now()
  const reservations = removeExpiredReservations(getReservations(), now)
  const ownReservation = reservations.find((reservation) => reservation.email === currentAccount.email)
  const fragment = document.createDocumentFragment()

  for (let laneNumber = 1; laneNumber <= 5; laneNumber += 1) {
    fragment.append(createLaneCard(laneNumber, reservations, now, ownReservation))
  }

  laneList.replaceChildren(fragment)
}

function openReservationDialog(laneNumber) {
  selectedLane = laneNumber
  reservationForm.reset()
  reservationError.hidden = true
  document.getElementById('reservation-title').textContent = `Reserve Lane ${laneNumber}`

  const start = new Date(Date.now() + 15 * 60 * 1000)
  start.setMinutes(Math.ceil(start.getMinutes() / 15) * 15, 0, 0)
  const end = new Date(start.getTime() + 60 * 60 * 1000)
  const startInput = document.getElementById('reservation-start')
  const endInput = document.getElementById('reservation-end')
  startInput.min = toLocalDateTimeValue(new Date())
  startInput.value = toLocalDateTimeValue(start)
  endInput.min = startInput.value
  endInput.value = toLocalDateTimeValue(end)
  reservationDialog.showModal()
}

function cancelReservation(reservationId) {
  try {
    const reservations = getReservations()
    const reservation = reservations.find((item) => item.id === reservationId)
    if (!reservation || reservation.email !== currentAccount.email) {
      showMessage('That reservation could not be found for your demo account.', true)
      renderLanes()
      return
    }

    saveReservations(reservations.filter((item) => item.id !== reservationId))
    showMessage('Your demo reservation has been cancelled.')
    renderLanes()
  } catch (error) {
    showMessage(error instanceof Error ? error.message : 'Could not cancel this reservation.', true)
  }
}

document.getElementById('reservation-start').addEventListener('change', (event) => {
  document.getElementById('reservation-end').min = event.currentTarget.value
})

document.getElementById('close-dialog').addEventListener('click', () => {
  reservationDialog.close()
})

reservationForm.addEventListener('submit', (event) => {
  event.preventDefault()
  reservationError.textContent = ''
  reservationError.hidden = true

  try {
    const purpose = document.getElementById('reservation-purpose').value
    const startValue = document.getElementById('reservation-start').value
    const endValue = document.getElementById('reservation-end').value
    const start = new Date(startValue)
    const end = new Date(endValue)
    const now = Date.now()

    if (!validPurposes.has(purpose)) {
      throw new Error('Choose a valid reservation purpose.')
    }
    if (!Number.isFinite(start.getTime()) || !Number.isFinite(end.getTime()) || start.getTime() < now || end <= start) {
      throw new Error('Choose a future start time and an end time after it.')
    }

    const reservations = removeExpiredReservations(getReservations(), now)
    if (reservations.some((reservation) => reservation.email === currentAccount.email)) {
      throw new Error('You can reserve one lane at a time. Cancel your current reservation first.')
    }
    const overlaps = reservations.some(
      (reservation) =>
        reservation.lane === selectedLane &&
        start.getTime() < Date.parse(reservation.end) &&
        end.getTime() > Date.parse(reservation.start),
    )
    if (overlaps) {
      throw new Error('That lane is already booked during the selected time. Choose another time or lane.')
    }

    reservations.push({
      id: crypto.randomUUID(),
      lane: selectedLane,
      email: currentAccount.email,
      name: currentAccount.name,
      plate: currentAccount.plate,
      purpose,
      start: start.toISOString(),
      end: end.toISOString(),
    })
    saveReservations(reservations)
    reservationDialog.close()
    showMessage(`Lane ${selectedLane} is reserved in this browser demo.`)
    renderLanes()
  } catch (error) {
    reservationError.textContent = error instanceof Error ? error.message : 'Could not save this demo reservation.'
    reservationError.hidden = false
  }
})

document.getElementById('signout-button').addEventListener('click', () => {
  sessionStorage.removeItem(SESSION_KEY)
  localStorage.removeItem(REMEMBERED_USER_KEY)
  window.location.assign('./index.html')
})

try {
  const accounts = getAccounts()
  const email = sessionStorage.getItem(SESSION_KEY) || localStorage.getItem(REMEMBERED_USER_KEY)
  currentAccount = accounts.find((account) => account.email === email)
  if (!currentAccount) {
    sessionStorage.removeItem(SESSION_KEY)
    localStorage.removeItem(REMEMBERED_USER_KEY)
    window.location.replace('./index.html')
  } else {
    document.getElementById('dashboard-user-name').textContent = currentAccount.name
    document.getElementById('dashboard-user-email').textContent = currentAccount.email
    document.getElementById('dashboard-user-plate').textContent = currentAccount.plate
    renderLanes()
    window.setInterval(() => {
      try {
        renderLanes()
      } catch (error) {
        showMessage(error instanceof Error ? error.message : 'Could not refresh demo lane availability.', true)
      }
    }, 60_000)
  }
} catch (error) {
  showMessage(error instanceof Error ? error.message : 'Could not load the demo account.', true)
}
