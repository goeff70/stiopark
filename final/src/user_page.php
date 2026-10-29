<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ./login/index.php');
    exit();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>USER-opark</title>
    <style>
      :root {
        --text: #f8fd73;
        --text-h: #08060d;
        --bg: #fff;
        --border: #e5e4e7;
        --code-bg: #f4f3ec;
        --accent: #aa3bff;
        --accent-bg: rgba(170, 59, 255, 0.1);
        --accent-border: rgba(170, 59, 255, 0.5);
        --social-bg: rgba(244, 243, 236, 0.5);
        --shadow: rgba(0, 0, 0, 0.1) 0 10px 15px -3px, rgba(0, 0, 0, 0.05) 0 4px 6px -2px;
        --sans: system-ui, 'Segoe UI', Roboto, sans-serif;
        --heading: system-ui, 'Segoe UI', Roboto, sans-serif;
        --mono: ui-monospace, Consolas, monospace;
        font: 18px/145% var(--sans);
        letter-spacing: 0.18px;
        color-scheme: light dark;
        color: var(--text);
        background: var(--bg);
        font-synthesis: none;
        text-rendering: optimizeLegibility;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
      }
      @media (prefers-color-scheme: dark) {
        :root {
          --text: #0800ff;
          --text-h: #f3f4f6;
          --bg: #16171d;
          --border: #2e303a;
          --code-bg: #1f2028;
          --accent: #c084fc;
          --accent-bg: rgba(192, 132, 252, 0.15);
          --accent-border: rgba(192, 132, 252, 0.5);
          --social-bg: rgba(47, 48, 58, 0.5);
          --shadow: rgba(0, 0, 0, 0.4) 0 10px 15px -3px, rgba(0, 0, 0, 0.25) 0 4px 6px -2px;
        }
      }
      @media (max-width: 1024px) {
        :root {
          font-size: 16px;
        }
      }
      html,
      body {
        height: 100%;
      }
      body {
        background-image: url(assets/bg.png);
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        background-attachment: fixed;
        margin: 0;
      }
      .reservationModal {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
        z-index: 1000;
      }
      .reservationModal.show {
        opacity: 1;
        visibility: visible;
      }
      .logoutModal {
        z-index: 1002;
      }
      .reservationDialog {
        width: min(360px, calc(100vw - 32px));
        background: #ffffff;
        color: #111827;
        border-radius: 14px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.28);
        border: 1px solid rgba(148, 163, 184, 0.5);
        padding: 26px 24px 20px;
        text-align: center;
      }
      .reservationTitle {
        margin: 0 0 12px;
        font-size: 1.1rem;
        font-weight: 800;
        color: #111827;
      }
      .reservationDialog p {
        margin: 0 0 18px;
        font-size: 0.95rem;
        color: #374151;
      }
      .reservationPurposeGroup {
        display: flex;
        flex-direction: column;
        gap: 8px;
        text-align: left;
        margin: 0 0 18px;
      }
      .reservationPurposeLabel {
        font-size: 0.8rem;
        font-weight: 700;
        color: #111827;
      }
      .reservationPurposeSelect {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid rgba(148, 163, 184, 0.7);
        border-radius: 8px;
        background: #ffffff;
        color: #111827;
        font-size: 0.9rem;
        box-sizing: border-box;
      }
      .reservationDialog button {
        border: none;
        background: #2563eb;
        color: #ffffff;
        border-radius: 8px;
        padding: 10px 22px;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.9rem;
      }
      .reservationDialog button:hover {
        background: #1d4ed8;
      }
      .logoutActions {
        display: flex;
        justify-content: center;
        gap: 8px;
      }
      #cancelLogoutBtn {
        background: #6b7280;
      }
      #cancelLogoutBtn:hover {
        background: #4b5563;
      }
      #reservationCancelBtn {
        display: none;
        margin-right: 8px;
        background: #6b7280;
      }
      #reservationCancelBtn:hover {
        background: #4b5563;
      }
      .laneNotice {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.45);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
        z-index: 1001;
      }
      .laneNotice.show {
        opacity: 1;
        visibility: visible;
      }
      .laneNoticeContent {
        width: min(320px, calc(100vw - 32px));
        background: #fff7ed;
        color: #7c2d12;
        border: 1px solid rgba(251, 146, 60, 0.6);
        border-radius: 12px;
        padding: 18px 16px;
        text-align: center;
        font-size: 0.8rem;
        font-weight: 800;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
      }
      #root {
        width: 1126px;
        max-width: 100%;
        margin: 0 auto;
        text-align: center;
        border-inline: 1px solid var(--border);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
      }
      header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
      }
      nav ul {
        display: flex;
        flex-wrap: wrap;
        list-style: none;
        padding: 0;
        margin: 0;
        gap: 8px;
      }
      nav a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 22px;
        border-radius: 8px;
        background: #2563eb;
        color: #ffffff;
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
        transition: background-color 0.15s ease;
      }
      nav a:hover {
        background: #1d4ed8;
      }
      nav a:focus-visible {
        outline: 3px solid #ffffff;
        outline-offset: 3px;
      }
      .statusLegend {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        justify-content: flex-start;
        width: fit-content;
        padding: 8px 12px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        margin: 12px 0 0 4px;
      }
      .legendItem {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #000000;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.02em;
      }
      .legendIcon {
        width: 16px;
        height: 16px;
        display: block;
        object-fit: contain;
      }
      .legendHelpWrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
      }
      .legendHelp {
        width: 22px;
        height: 22px;
        border: 1px solid rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        color: #111827;
        font-size: 0.88rem;
        font-weight: 800;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        appearance: none;
        -webkit-appearance: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }
      .legendPreview {
        position: absolute;
        left: calc(100% + 12px);
        top: 50%;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        width: 420px;
        max-width: 65vw;
        box-sizing: border-box;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.5);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25);
        opacity: 0;
        visibility: hidden;
        pointer-events: auto;
        transition: opacity 0.18s ease, visibility 0.18s ease;
        z-index: 5;
      }
      .legendPreview img {
        display: block;
        width: 100%;
        max-width: 100%;
        height: auto;
        border-radius: 6px;
      }
      #reserveUsingQrButton {
        flex: 0 0 auto;
        width: 112px;
        min-height: 42px;
        border: 0;
        border-radius: 6px;
        padding: 8px;
        background: #166534;
        color: #ffffff;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
      }
      #reserveUsingQrButton:hover {
        background: #14532d;
      }
      #reserveUsingQrButton:focus-visible {
        outline: 3px solid #16a34a;
        outline-offset: 2px;
      }
      .qrReservationDialog {
        width: min(360px, calc(100vw - 32px));
      }
      #qrCodeContainer {
        display: grid;
        place-items: center;
        width: 224px;
        min-height: 224px;
        max-width: 100%;
        margin: 0 auto 16px;
        background: #ffffff;
      }
      #qrCodeContainer img,
      #qrCodeContainer canvas {
        max-width: 100%;
        height: auto;
      }
      #qrCloseBtn {
        background: #6b7280;
      }
      .legendHelpWrapper:hover .legendPreview,
      .legendHelpWrapper:focus-within .legendPreview {
        opacity: 1;
        visibility: visible;
      }
      .lanesContainer {
        display: flex;
        gap: 10px;
        width: 100%;
        min-height: 80vh;
        flex: 1;
        margin: 5px 0 0 0;
        align-items: stretch;
      }
      .laneCard {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
      }
      .laneButton {
        padding: 8px 10px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        min-height: 0;
        height: 75%;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: clamp(0.7rem, 1.2vw, 1rem);
        transition: background-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15);
      }
      .laneButton.is-vacant {
        background-color: #22c55e;
      }
      .laneButton.is-occupied {
        background-color: #ef4444;
      }
      .laneButton.is-clicked {
        background-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.35);
      }
      .laneButton.is-unknown {
        background-color: #6b7280;
      }
      .laneButton:hover {
        transform: translateY(-1px);
      }
      .laneButton a {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        max-width: 100%;
        overflow-wrap: anywhere;
        color: #ffffff;
        text-decoration: none;
        font-weight: 700;
      }
      .lane-owner {
        font-size: 1em;
      }
      .timeSelectorGroup {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 6px 8px;
        border-radius: 6px;
        background: rgba(13, 17, 33, 0.38);
        border: 1px solid rgba(255, 255, 255, 0.08);
      }
      .timeLabel {
        font-size: 0.72rem;
        color: #f5f5f5;
        font-weight: 600;
        text-align: left;
      }
      .timeSelector {
        width: 100%;
        padding: 6px 8px;
        border-radius: 5px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.92);
        color: #111827;
        font-size: 0.82rem;
        box-sizing: border-box;
      }
      footer {
        margin-top: auto;
      }
      footer p {
        margin: 0;
      }
    </style>
  </head>
  <body>
    <div id="root">
      <div id="reservationModal" class="reservationModal" role="dialog" aria-modal="true" aria-labelledby="reservationTitle">
        <div class="reservationDialog">
          <h2 id="reservationTitle" class="reservationTitle">Reservation</h2>
          <p id="reservationMessage">Lane reserved</p>
          <div class="reservationPurposeGroup">
            <label class="reservationPurposeLabel" for="reservationPurpose">Purpose of reservation</label>
            <select id="reservationPurpose" class="reservationPurposeSelect">
              <option value="" selected disabled>Select purpose</option>
              <option value="Enrollment">Enrollment</option>
              <option value="Payment">Payment</option>
              <option value="Classes">Classes</option>
              <option value="TESDA">TESDA</option>
                <option value="Other">Other</option>
            </select>
          </div>
          <button id="reservationCancelBtn" type="button">Cancel</button>
          <button id="reservationConfirmBtn" type="button">OK</button>
        </div>
      </div>
      <div id="qrReservationModal" class="reservationModal" role="dialog" aria-modal="true" aria-labelledby="qrReservationTitle">
        <div class="reservationDialog qrReservationDialog">
          <h2 id="qrReservationTitle" class="reservationTitle">Reserve using QR</h2>
          <p id="qrReservationMessage" aria-live="polite">Preparing your reservation QR...</p>
          <div id="qrCodeContainer"></div>
          <button id="qrCloseBtn" type="button">Close</button>
        </div>
      </div>
      <div id="logoutModal" class="reservationModal logoutModal" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
        <div class="reservationDialog">
          <h2 id="logoutTitle" class="reservationTitle">Confirm logout</h2>
          <p>Are you sure you want to log out?</p>
          <div class="logoutActions">
            <button id="cancelLogoutBtn" type="button">Cancel</button>
            <button id="confirmLogoutBtn" type="button">Log Out</button>
          </div>
        </div>
      </div>
      <div id="aboutModal" class="reservationModal infoModal" role="dialog" aria-modal="true" aria-labelledby="aboutTitle">
        <div class="reservationDialog">
          <h2 id="aboutTitle" class="reservationTitle">About oPark</h2>
          <p>oPark is a project developed by Ged Patrick Diayon, Jennelyn Cabanizas, Ivanah Dela Torre, and Merryle Lumanog in compliance with their capstone requirement S.Y. 2026 - 2027</p>
          <button class="infoModalClose" type="button">Close</button>
        </div>
      </div>
      <div id="contactModal" class="reservationModal infoModal" role="dialog" aria-modal="true" aria-labelledby="contactTitle">
        <div class="reservationDialog">
          <h2 id="contactTitle" class="reservationTitle">Contact</h2>
          <p>For further inquiries about oPark, please contact: Ma'am Erica Canubas - 09xx-xxx-xxxx</p>
          <button class="infoModalClose" type="button">Close</button>
        </div>
      </div>
      <div id="servicesModal" class="reservationModal infoModal" role="dialog" aria-modal="true" aria-labelledby="servicesTitle">
        <div class="reservationDialog">
          <h2 id="servicesTitle" class="reservationTitle">STI Services</h2>
          <p><a href="https://helpdesk.sti.edu/" target="_blank" rel="noopener noreferrer">STI Helpdesk</a></p>
          <p><a href="https://inquire.sti.edu/" target="_blank" rel="noopener noreferrer">STI Inquire</a></p>
          <button class="infoModalClose" type="button">Close</button>
        </div>
      </div>
      <header>
        <nav>
          <ul>
            <li><a class="logout-link" href="./logout.php">Logout</a></li>
            <li><a href="#aboutModal" data-info-modal="aboutModal">About</a></li>
            <li><a href="#contactModal" data-info-modal="contactModal">Contact</a></li>
            <li><a href="#servicesModal" data-info-modal="servicesModal">Services</a></li>
          </ul>
        </nav>
      </header>
      <div class="statusLegend" aria-label="Parking status legend">
        <div id="laneNotice" class="laneNotice" aria-live="assertive">
          <div class="laneNoticeContent">Unreserve the current lane first.</div>
        </div>
        <div class="legendItem">
          <img src="assets/green-square.svg" alt="Vacant" class="legendIcon" />
          <span>Vacant</span>
        </div>
        <div class="legendItem">
          <img src="assets/red-square.svg" alt="Occupied" class="legendIcon" />
          <span>Occupied</span>
        </div>
        <div class="legendItem">
          <img src="assets/blue-square.svg" alt="Reserved" class="legendIcon" />
          <span>Reserved</span>
        </div>
        <div class="legendHelpWrapper" tabindex="0" aria-label="Parking legend help">
          <button class="legendHelp" type="button" aria-label="Show parking legend">?</button>
          <div class="legendPreview">
            <img src="assets/parking%20legend.jpg" alt="Parking legend" />
          </div>
        </div>
        <button id="reserveUsingQrButton" type="button">Reserve using QR</button>
      </div>
      <div class="lanesContainer">
        <div class="laneCard">
          <button class="laneButton"><a href="#">Lane 1</a></button>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane1-start">Start Time</label>
            <select id="lane1-start" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane1-end">End Time</label>
            <select id="lane1-end" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
        </div>
        <div class="laneCard">
          <button class="laneButton"><a href="#">Lane 2</a></button>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane2-start">Start Time</label>
            <select id="lane2-start" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane2-end">End Time</label>
            <select id="lane2-end" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
        </div>
        <div class="laneCard">
          <button class="laneButton"><a href="#">Lane 3</a></button>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane3-start">Start Time</label>
            <select id="lane3-start" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane3-end">End Time</label>
            <select id="lane3-end" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
        </div>
        <div class="laneCard">
          <button class="laneButton"><a href="#">Lane 4</a></button>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane4-start">Start Time</label>
            <select id="lane4-start" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane4-end">End Time</label>
            <select id="lane4-end" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
        </div>
        <div class="laneCard">
          <button class="laneButton"><a href="#">Lane 5</a></button>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane5-start">Start Time</label>
            <select id="lane5-start" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
          <div class="timeSelectorGroup">
            <label class="timeLabel" for="lane5-end">End Time</label>
            <select id="lane5-end" class="timeSelector">
              <option value="7:00 AM">7:00 AM</option>
              <option value="7:30 AM">7:30 AM</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="8:30 AM">8:30 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="9:30 AM">9:30 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="10:30 AM">10:30 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="11:30 AM">11:30 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="12:30 PM">12:30 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="1:30 PM">1:30 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="2:30 PM">2:30 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="3:30 PM">3:30 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="4:30 PM">4:30 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="5:30 PM">5:30 PM</option>
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
        </div>
      </div>
      <footer>
        <p>&copy; <?php echo date('Y'); ?> STI College Malaybalay</p>
      </footer>
    </div>
    <script src="../node_modules/qrcodejs/qrcode.min.js"></script>
    <script>
      const startTimes = [
        '7:00 AM', '7:30 AM', '8:00 AM', '8:30 AM', '9:00 AM', '9:30 AM',
        '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM', '12:00 PM', '12:30 PM',
        '1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM',
        '4:00 PM', '4:30 PM', '5:00 PM', '5:30 PM', '6:00 PM', '6:30 PM',
        '7:00 PM', '7:30 PM'
      ];

      const endTimes = [
        '7:30 AM', '8:00 AM', '8:30 AM', '9:00 AM', '9:30 AM', '10:00 AM',
        '10:30 AM', '11:00 AM', '11:30 AM', '12:00 PM', '12:30 PM', '1:00 PM',
        '1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM', '4:00 PM',
        '4:30 PM', '5:00 PM', '5:30 PM', '6:00 PM', '6:30 PM', '7:00 PM',
        '7:30 PM', '8:00 PM'
      ];

      
      const reservationModal = document.getElementById('reservationModal');
      const reservationMessage = document.getElementById('reservationMessage');
      const reservationConfirmBtn = document.getElementById('reservationConfirmBtn');
      const reservationCancelBtn = document.getElementById('reservationCancelBtn');
      const reservationPurpose = document.getElementById('reservationPurpose');
      const qrReservationModal = document.getElementById('qrReservationModal');
      const qrReservationMessage = document.getElementById('qrReservationMessage');
      const qrCodeContainer = document.getElementById('qrCodeContainer');
      const reserveUsingQrButton = document.getElementById('reserveUsingQrButton');
      const laneNotice = document.getElementById('laneNotice');
      let selectedLaneForReservation = '';
      let reservationMode = '';
      let hasActiveReservation = false;
      const logoutLink = document.querySelector('.logout-link');
      const logoutModal = document.getElementById('logoutModal');
      document.querySelectorAll('[data-info-modal]').forEach((link) => {
        const modal = document.getElementById(link.dataset.infoModal);
        link.addEventListener('click', (event) => {
          event.preventDefault();
          modal?.classList.add('show');
        });
      });
      document.querySelectorAll('.infoModal').forEach((modal) => {
        modal.querySelector('.infoModalClose')?.addEventListener('click', () => {
          modal.classList.remove('show');
        });
        modal.addEventListener('click', (event) => {
          if (event.target === modal) modal.classList.remove('show');
        });
      });
      logoutLink?.addEventListener('click', (event) => {
        event.preventDefault();
        logoutModal.classList.add('show');
      });
      document.getElementById('cancelLogoutBtn')?.addEventListener('click', () => {
        logoutModal.classList.remove('show');
      });
      document.getElementById('confirmLogoutBtn')?.addEventListener('click', () => {
        window.location.assign(logoutLink.href);
      });
      logoutModal?.addEventListener('click', (event) => {
        if (event.target === logoutModal) logoutModal.classList.remove('show');
      });
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          logoutModal?.classList.remove('show');
          qrReservationModal?.classList.remove('show');
        }
      });

      const laneNumberFromButton = (button) => {
        return Number(button.querySelector('a')?.textContent?.match(/\d+/)?.[0]);
      };

      const sendLaneAction = async (payload) => {
        const response = await fetch('./lanes_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (!response.ok) {
          const error = new Error(result.error || 'Unable to update lane.');
          error.code = result.code;
          throw error;
        }
        return result;
      };

      const refreshLaneStates = async () => {
        const response = await fetch('./lanes_api.php', { cache: 'no-store' });
        if (!response.ok) throw new Error('Unable to load lane status.');
        const result = await response.json();
        hasActiveReservation = result.lanes.some((lane) => lane.is_reserved && lane.is_mine);

        result.lanes.forEach((lane) => {
          const button = [...document.querySelectorAll('.laneButton')]
            .find((item) => laneNumberFromButton(item) === lane.lane_number);
          if (!button) return;

          button.dataset.sensor = lane.sensor_status;
          button.dataset.reserved = String(lane.is_reserved);
          button.dataset.canCancel = String(lane.can_cancel);
          const laneLink = button.querySelector('a');
          if (lane.is_reserved) {
            laneLink.replaceChildren();
            if (lane.is_mine) {
              const ownerLabel = document.createElement('span');
              ownerLabel.className = 'lane-owner';
              ownerLabel.textContent = lane.plate_number ? `(You) · ${lane.plate_number}` : '(You)';
              laneLink.appendChild(ownerLabel);
            }
            const reservationLabel = document.createElement('span');
            reservationLabel.textContent = `Lane ${lane.lane_number} reserved for ${lane.reservation_purpose || 'reservation'}`;
            laneLink.appendChild(reservationLabel);
          } else {
            laneLink.textContent = `Lane ${lane.lane_number}`;
          }
          applyLaneButtonState(button, lane.sensor_status);
        });
        if (result.expired_reservations?.length) {
          reservationPurpose.value = '';
          reservationPurpose.closest('.reservationPurposeGroup').style.display = 'none';
          reservationCancelBtn.style.display = 'none';
          reservationMode = 'notice';
          reservationMessage.textContent = 'Reservation time has expired';
          reservationModal.classList.add('show');
        }
      };

      const closeReservationModal = () => {
        if (!reservationModal) return;
        reservationModal.classList.remove('show');
      };

      const showReservationPopup = (laneName) => {
        if (!reservationModal || !reservationMessage || !reservationPurpose) return;

        selectedLaneForReservation = laneName;
        reservationMode = 'reserve';
        reservationPurpose.value = '';
        reservationPurpose.closest('.reservationPurposeGroup').style.display = 'flex';
        reservationCancelBtn.style.display = 'inline-block';
        reservationMessage.textContent = `${laneName} has been reserved.`;
        reservationModal.classList.add('show');
      };

      const showOccupiedLanePopup = () => {
        if (!reservationModal || !reservationMessage) return;

        reservationPurpose.closest('.reservationPurposeGroup').style.display = 'none';
        reservationCancelBtn.style.display = 'none';
        reservationMode = 'notice';
        reservationMessage.textContent = 'Lanes currently occupied cannot be reserved';
        reservationModal.classList.add('show');
      };

      const showOneLaneLimitPopup = () => {
        reservationPurpose.closest('.reservationPurposeGroup').style.display = 'none';
        reservationCancelBtn.style.display = 'none';
        reservationMode = 'notice';
        reservationMessage.textContent = 'You can reserve only one lane at a time. Cancel your current reservation before choosing another.';
        reservationModal.classList.add('show');
      };

      const showReservationCanceledPopup = (laneName) => {
        if (!reservationModal || !reservationMessage || !reservationPurpose) return;

        selectedLaneForReservation = laneName;
        reservationMode = 'cancelled';
        reservationPurpose.value = '';
        reservationPurpose.closest('.reservationPurposeGroup').style.display = 'none';
        reservationCancelBtn.style.display = 'none';
        reservationMessage.textContent = 'Reservation canceled.';
        reservationModal.classList.add('show');
      };

      const showCancelReservationPopup = (laneName) => {
        if (!reservationModal || !reservationMessage || !reservationPurpose) return;

        selectedLaneForReservation = laneName;
        reservationMode = 'cancel';
        reservationPurpose.value = '';
        reservationPurpose.closest('.reservationPurposeGroup').style.display = 'none';
        reservationCancelBtn.style.display = 'inline-block';
        reservationMessage.textContent = 'Confirm Cancelation?';
        reservationModal.classList.add('show');
      };

      const showLaneNotice = () => {
        if (!laneNotice) return;

        laneNotice.classList.add('show');
        clearTimeout(showLaneNotice.timeoutId);
        showLaneNotice.timeoutId = setTimeout(() => {
          laneNotice.classList.remove('show');
        }, 1800);
      };

      if (reservationConfirmBtn && reservationPurpose) {
        reservationConfirmBtn.addEventListener('click', async () => {
          const purposeGroup = reservationPurpose.closest('.reservationPurposeGroup');

          if (reservationMode === 'reserve') {
            const purpose = reservationPurpose.value;
            if (!purpose) {
              reservationMessage.textContent = 'Please select a purpose before confirming the reservation.';
              return;
            }

            const laneNumber = Number(selectedLaneForReservation.match(/\d+/)?.[0]);
            const laneButton = [...document.querySelectorAll('.laneButton')]
              .find((button) => laneNumberFromButton(button) === laneNumber);
            const [startSelect, endSelect] = laneButton?.closest('.laneCard')?.querySelectorAll('.timeSelector') || [];

            try {
              await sendLaneAction({
                action: 'reserve',
                lane_number: laneNumber,
                purpose,
                start_time: startSelect?.value,
                end_time: endSelect?.value,
              });
              await refreshLaneStates();
              reservationMessage.textContent = `${selectedLaneForReservation} has been reserved for ${purpose}.`;
              reservationPurpose.value = '';
              purposeGroup.style.display = 'none';
              reservationCancelBtn.style.display = 'none';
              reservationMode = 'reserved';
            } catch (error) {
              reservationMessage.textContent = error.message;
              if (error.code === 'one_lane_limit') {
                reservationPurpose.value = '';
                showOneLaneLimitPopup();
              }
            }
            return;
          }

          if (reservationMode === 'cancel') {
            const laneNumber = Number(selectedLaneForReservation.match(/\d+/)?.[0]);
            try {
              await sendLaneAction({ action: 'cancel', lane_number: laneNumber });
              await refreshLaneStates();
              reservationMessage.textContent = `${selectedLaneForReservation} has been unreserved.`;
              reservationCancelBtn.style.display = 'none';
              reservationMode = 'unreserved';
            } catch (error) {
              reservationMessage.textContent = error.message;
            }
            return;
          }

          closeReservationModal();
        });
      }

      if (reservationCancelBtn) {
        reservationCancelBtn.addEventListener('click', () => {
          const canceledReservation = reservationMode === 'reserve';

          reservationMode = 'cancelled';
          reservationCancelBtn.style.display = 'none';
          reservationPurpose.value = '';
          reservationPurpose.closest('.reservationPurposeGroup').style.display = 'none';
          reservationMessage.textContent = canceledReservation
            ? 'Reservation canceled.'
            : 'Unreservation canceled.';
        });
      }

      const parseTimeValue = (value) => {
        const [time, period] = value.split(' ');
        let [hours, minutes] = time.split(':').map(Number);

        if (period === 'AM' && hours === 12) {
          hours = 0;
        } else if (period === 'PM' && hours !== 12) {
          hours += 12;
        }

        return hours * 60 + minutes;
      };

      const getNextStartTime = () => {
        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();
        const nextUnitMinutes = Math.ceil((currentMinutes + 1) / 30) * 30;
        const openingMinutes = parseTimeValue(startTimes[0]);
        const closingStartMinutes = parseTimeValue(startTimes[startTimes.length - 1]);

        if (nextUnitMinutes <= openingMinutes) {
          return startTimes[0];
        }

        if (nextUnitMinutes > closingStartMinutes) {
          return null;
        }

        return startTimes.find((value) => parseTimeValue(value) === nextUnitMinutes) || null;
      };

      reserveUsingQrButton?.addEventListener('click', async () => {
        if (hasActiveReservation) {
          qrReservationMessage.textContent = 'Cancel your current reservation before creating a QR reservation.';
          qrCodeContainer.replaceChildren();
          qrReservationModal.classList.add('show');
          return;
        }

        const startTime = getNextStartTime();
        const endTime = endTimes.find((value) => startTime && parseTimeValue(value) > parseTimeValue(startTime));
        qrCodeContainer.replaceChildren();
        qrReservationModal.classList.add('show');

        if (!startTime || !endTime) {
          qrReservationMessage.textContent = 'There are no more reservation times available today.';
          return;
        }

        qrReservationMessage.textContent = 'Preparing a one-time QR for the next available slot...';
        try {
          const response = await fetch('./qr_reservation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'create',
              purpose: 'Other',
              start_time: startTime,
              end_time: endTime,
            }),
          });
          const result = await response.json();
          if (!response.ok) throw new Error(result.error || 'Unable to create a reservation QR.');
          if (typeof QRCode !== 'function') throw new Error('The QR generator could not be loaded.');

          new QRCode(qrCodeContainer, {
            text: result.url,
            width: 208,
            height: 208,
            colorDark: '#111827',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M,
          });
          qrReservationMessage.textContent = `Scan within 5 minutes to reserve the first vacant lane for Other, ${startTime} to ${endTime}.`;
        } catch (error) {
          qrCodeContainer.replaceChildren();
          qrReservationMessage.textContent = error.message;
        }
      });

      document.getElementById('qrCloseBtn')?.addEventListener('click', () => {
        qrReservationModal?.classList.remove('show');
      });
      qrReservationModal?.addEventListener('click', (event) => {
        if (event.target === qrReservationModal) qrReservationModal.classList.remove('show');
      });

      const applyLaneButtonState = (button, sensorState) => {
        button.classList.remove('is-vacant', 'is-occupied', 'is-clicked', 'is-unknown');

        if (button.dataset.reserved === 'true') {
          button.classList.add('is-clicked');
        } else if (sensorState === 'occupied') {
          button.classList.add('is-occupied');
        } else if (sensorState === 'unknown') {
          button.classList.add('is-unknown');
        } else {
          button.classList.add('is-vacant');
        }
      };

      document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.laneCard').forEach((laneCard) => {
          const laneButton = laneCard.querySelector('.laneButton');
          const [startSelect, endSelect] = laneCard.querySelectorAll('.timeSelector');
          const laneLabel = laneCard.querySelector('.laneButton a')?.textContent?.trim() || 'Lane';
          const laneNumber = Number(laneLabel.match(/\d+/)?.[0]);

          if (laneButton) {
            laneButton.dataset.laneNumber = String(laneNumber);
            laneButton.dataset.sensor = 'unknown';
            laneButton.dataset.reserved = 'false';
            laneButton.dataset.clicked = 'false';
            laneButton.dataset.canCancel = 'false';
            applyLaneButtonState(laneButton, 'unknown');

            laneButton.addEventListener('click', (event) => {
              event.preventDefault();

              if (laneButton.dataset.sensor === 'occupied' && laneButton.dataset.reserved !== 'true') {
                showOccupiedLanePopup();
                return;
              }

              const isAlreadyReserved = laneButton.dataset.reserved === 'true';
              if (isAlreadyReserved) {
                if (laneButton.dataset.canCancel === 'true') {
                  showCancelReservationPopup(laneLabel);
                } else {
                  reservationMode = 'notice';
                  reservationPurpose.closest('.reservationPurposeGroup').style.display = 'none';
                  reservationCancelBtn.style.display = 'none';
                  reservationMessage.textContent = 'This lane is reserved by another user.';
                  reservationModal.classList.add('show');
                }
                return;
              }

              if (hasActiveReservation) {
                showOneLaneLimitPopup();
                return;
              }

              showReservationPopup(laneLabel);
            });
          }

          if (!startSelect || !endSelect) {
            return;
          }

          const nextStartTime = getNextStartTime();

          if (!nextStartTime) {
            startSelect.innerHTML = '<option value="">No more start times today</option>';
            endSelect.innerHTML = '<option value="">No available end time</option>';
            startSelect.disabled = true;
            endSelect.disabled = true;
            return;
          }

          const nextStartIndex = startTimes.indexOf(nextStartTime);
          const availableStartTimes = startTimes.slice(nextStartIndex);

          startSelect.innerHTML = availableStartTimes
            .map((value) => `<option value="${value}">${value}</option>`)
            .join('');
          startSelect.disabled = false;
          endSelect.disabled = false;

          const updateEndOptions = () => {
            const startMinutes = parseTimeValue(startSelect.value);
            const validEndTimes = endTimes.filter((value) => parseTimeValue(value) > startMinutes);

            endSelect.innerHTML = validEndTimes
              .map((value) => `<option value="${value}">${value}</option>`)
              .join('');

            if (!validEndTimes.length) {
              endSelect.innerHTML = `<option value="${endTimes[endTimes.length - 1]}">${endTimes[endTimes.length - 1]}</option>`;
              return;
            }

            const nextEndOption = validEndTimes[0];
            endSelect.value = nextEndOption;
          };

          startSelect.addEventListener('change', updateEndOptions);
          startSelect.value = nextStartTime;
          updateEndOptions();
        });

        refreshLaneStates().catch(() => {});
        window.setInterval(() => refreshLaneStates().catch(() => {}), 3000);
      });
    </script>
  </body>
</html>