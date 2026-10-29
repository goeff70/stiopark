function App() {
  return (
    <main className="welcome-page">
      <div className="welcome-content">
        <p className="welcome-kicker">STI College</p>
        <h1>Welcome to oPark</h1>
        <a className="welcome-button" href="./src/login/index.php">
          Go to Login
          <span aria-hidden="true">&rarr;</span>
        </a>
      </div>
    </main>
  )
}

export default App