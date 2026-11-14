import { Navigate, NavLink, Outlet, useLocation } from 'react-router'
import { useAuth } from '../auth/useAuth.js'

export default function AdminLayout() {
  const { user, isAdmin, loading } = useAuth()
  const location = useLocation()

  if (loading) {
    return null
  }
  if (!user) {
    return <Navigate to="/login" state={{ from: location.pathname }} replace />
  }
  if (!isAdmin) {
    return (
      <div className="card">
        <h1>Brak dostępu</h1>
        <p>Ta część aplikacji jest dostępna tylko dla administratora.</p>
      </div>
    )
  }

  return (
    <>
      <nav className="admin-nav">
        <NavLink to="/admin/tournaments">Turnieje</NavLink>
        <NavLink to="/admin/courts">Korty</NavLink>
        <NavLink to="/admin/users">Użytkownicy</NavLink>
      </nav>
      <Outlet />
    </>
  )
}
