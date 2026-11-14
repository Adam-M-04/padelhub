import { Link, NavLink, Outlet, useNavigate } from 'react-router'
import { useAuth } from '../auth/useAuth.js'

export default function Layout() {
  const { user, loading, logout } = useAuth()
  const navigate = useNavigate()

  function handleLogout() {
    logout()
    navigate('/')
  }

  return (
    <>
      <header className="header">
        <div className="header-inner">
          <Link to="/" className="logo">PadelHub</Link>
          <nav className="nav">
            <NavLink to="/" end>Turnieje</NavLink>
          </nav>
          <div className="account">
            {loading ? null : user ? (
              <>
                <span>{user.firstName} {user.lastName}</span>
                <button type="button" className="button-secondary" onClick={handleLogout}>Wyloguj</button>
              </>
            ) : (
              <>
                <NavLink to="/login">Zaloguj</NavLink>
                <NavLink to="/register">Zarejestruj</NavLink>
              </>
            )}
          </div>
        </div>
      </header>
      <main className="main">
        <Outlet />
      </main>
    </>
  )
}
