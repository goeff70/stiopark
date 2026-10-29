function Header() {
    return (
        <header style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '20px' }}>
            <nav>
                <ul style={{ display: 'flex', listStyle: 'none', padding: 0, margin: 0, gap: '15px' }}>
                    <li><a href="http://localhost/vite-opark/src/login/index.php">Login</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Services</a></li>
                </ul>
            </nav>
        </header>
    );
}

export default Header