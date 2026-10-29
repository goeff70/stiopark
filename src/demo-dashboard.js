const ACCOUNTS_KEY = 'opark-demo-accounts'
const SESSION_KEY = 'opark-demo-session'
const REMEMBERED_USER_KEY = 'opark-demo-remembered-user'
const RESERVATIONS_KEY = 'opark-demo-reservations'
const laneList = document.getElementById('lane-list')
const reservationDialog = document.getElementById('reservation-dialog')
const reservationForm = document.getElementById('reservation-form')
const reservationError = document.getElementById('reservation-error')
const message = document.getElementById('dashboard-message')
const messageContent = message.querySelector('.dashboard-message-content')
const validPurposes = new Set(['Enrollment', 'Payment', 'Classes', 'TESDA', 'Other'])
const startTimes = [
  '7:00 AM', '7:30 AM', '8:00 AM', '8:30 AM', '9:00 AM', '9:30 AM',
  '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM', '12:00 PM', '12:30 PM',
  '1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM',
  '4:00 PM', '4:30 PM', '5:00 PM', '5:30 PM', '6:00 PM', '6:30 PM',
  '7:00 PM', '7:30 PM',
]
const endTimes = [
  '7:30 AM', '8:00 AM', '8:30 AM', '9:00 AM', '9:30 AM', '10:00 AM',
  '10:30 AM', '11:00 AM', '11:30 AM', '12:00 PM', '12:30 PM', '1:00 PM',
  '1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM', '4:00 PM',
  '4:30 PM', '5:00 PM', '5:30 PM', '6:00 PM', '6:30 PM', '7:00 PM',
  '7:30 PM', '8:00 PM',
]
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
  messageContent.textContent = text
  message.dataset.error = String(isError)
  message.hidden = false
  window.setTimeout(() => {
    if (messageContent.textContent === text) message.hidden = true
  }, 3000)
}

function parseTimeValue(value) {
  const [time, period] = value.split(' ')
  let [hours, minutes] = time.split(':').map(Number)
  if (period === 'AM' && hours === 12) hours = 0
  else if (period === 'PM' && hours !== 12) hours += 12
  return hours * 60 + minutes
}

function getLocalDateTimeValue(date) {
  const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000)
  return localDate.toISOString().slice(0, 16)
}

function dateForSlot(value, baseDate) {
  const date = new Date(baseDate)
  const [time, period] = value.split(' ')
  let [hours, minutes] = time.split(':').map(Number)
  if (period === 'AM' && hours === 12) hours = 0
  else if (period === 'PM' && hours !== 12) hours += 12
  date.setHours(hours, minutes, 0, 0)
  return date
}

function createTimeSelector(laneNumber, kind, times, selectedTime) {
  const group = document.createElement('div')
  group.className = 'timeSelectorGroup'

  const label = document.createElement('label')
  label.className = 'timeLabel'
  label.htmlFor = `lane${laneNumber}-${kind}`
  label.textContent = kind === 'start' ? 'Start Time' : 'End Time'

  const select = document.createElement('select')
  select.className = 'timeSelector'
  select.id = `lane${laneNumber}-${kind}`
  times.forEach((time) => {
    const option = document.createElement('option')
    option.value = time
    option.textContent = time
    option.selected = time === selectedTime
    select.append(option)
  })

  group.append(label, select)
  return group
}

function getDefaultTimes(now) {
  const currentMinutes = now.getHours() * 60 + now.getMinutes()
  const nextSlotMinutes = Math.ceil((currentMinutes + 1) / 30) * 30
  const nextStart = startTimes.find((time) => parseTimeValue(time) >= nextSlotMinutes)
  if (!nextStart) return { start: startTimes[0], end: endTimes[2] }

  const nextStartMinutes = parseTimeValue(nextStart)
  const nextEnd = endTimes.find((time) => parseTimeValue(time) > nextStartMinutes)
  return { start: nextStart, end: nextEnd || endTimes[endTimes.length - 1] }
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
  const card = document.createElement('div')
  card.className = 'laneCard'

  const button = document.createElement('button')
  button.type = 'button'
  button.className = 'laneButton'

  const buttonContent = document.createElement('span')
  buttonContent.className = 'laneButtonContent'
  const laneName = document.createElement('span')
  laneName.textContent = `Lane ${laneNumber}`
  const laneState = document.createElement('span')
  laneState.className = 'laneButtonState'
  buttonContent.append(laneName, laneState)
  button.append(buttonContent)

  const laneReservation = reservations.find((reservation) => reservation.lane === laneNumber)
  const isMine = laneReservation?.email === currentAccount.email
  const isOccupied = laneReservation &&
    Date.parse(laneReservation.start) <= now &&
    Date.parse(laneReservation.end) > now

  if (isMine) {
    button.classList.add('is-clicked')
    button.disabled = true
    laneState.textContent = 'Reserved by you'
  } else if (isOccupied) {
    button.classList.add('is-occupied')
    button.disabled = true
    laneState.textContent = 'Reserved'
  } else {
    button.classList.add('is-vacant')
    button.disabled = Boolean(ownReservation)
    laneState.textContent = laneReservation ? 'Booked later' : 'Vacant'
    if (!button.disabled) button.addEventListener('click', () => openReservationDialog(laneNumber))
  }

  const times = getDefaultTimes(new Date(now))
  const startSelector = createTimeSelector(laneNumber, 'start', startTimes, times.start)
  const endSelector = createTimeSelector(laneNumber, 'end', endTimes, times.end)
  const details = document.createElement('p')
  details.className = 'laneReservationDetails'
  details.textContent = laneReservation
    ? `${isMine ? 'Your reservation' : 'Booked'}: ${reservationDetails(laneReservation)}`
    : ownReservation
      ? 'Cancel your current reservation first.'
      : 'Select a time and click the lane to reserve.'

  card.append(button, startSelector, endSelector, details)
  if (isMine) {
    const cancelButton = document.createElement('button')
    cancelButton.type = 'button'
    cancelButton.className = 'laneCancelButton'
    cancelButton.textContent = 'Unreserve'
    cancelButton.addEventListener('click', () => cancelReservation(laneReservation.id))
    card.append(cancelButton)
  }
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

  const now = new Date()
  const startTime = document.getElementById(`lane${laneNumber}-start`).value
  const endTime = document.getElementById(`lane${laneNumber}-end`).value
  const start = dateForSlot(startTime, now)
  if (start <= now) start.setDate(start.getDate() + 1)
  const end = dateForSlot(endTime, start)
  if (parseTimeValue(endTime) <= parseTimeValue(startTime)) end.setDate(end.getDate() + 1)
  const startInput = document.getElementById('reservation-start')
  const endInput = document.getElementById('reservation-end')
  startInput.min = getLocalDateTimeValue(now)
  startInput.value = getLocalDateTimeValue(start)
  endInput.min = startInput.value
  endInput.value = getLocalDateTimeValue(end)
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

document.getElementById('current-year').textContent = new Date().getFullYear()
document.getElementById('signout-button').addEventListener('click', (event) => {
  event.preventDefault()
  sessionStorage.removeItem(SESSION_KEY)
  localStorage.removeItem(REMEMBERED_USER_KEY)
  window.location.assign('./index.html')
})

const infoContent = {
  about: ['About oPark', 'oPark is a parking reservation demo for STI College Malaybalay.'],
  contact: ['Contact', 'For inquiries, please contact the STI College Malaybalay administration.'],
  services: ['STI Services', 'This browser-only demo does not connect to STI services.'],
}

document.querySelectorAll('[data-info]').forEach((link) => {
  link.addEventListener('click', (event) => {
    event.preventDefault()
    const [title, content] = infoContent[link.dataset.info]
    document.getElementById('info-title').textContent = title
    document.getElementById('info-message').textContent = content
    document.getElementById('info-dialog').showModal()
  })
})

document.getElementById('close-info-dialog').addEventListener('click', () => {
  document.getElementById('info-dialog').close()
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
