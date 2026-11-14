import { useEffect, useState } from 'react'
import { apiFetch } from '../../api.js'
import { playerName } from '../../labels.js'

export default function AdminUsersPage() {
  const [users, setUsers] = useState(null)
  const [error, setError] = useState(false)

  useEffect(() => {
    apiFetch('/admin/users')
      .then(setUsers)
      .catch(() => setError(true))
  }, [])

  if (error) {
    return <p className="error">Nie udało się pobrać użytkowników.</p>
  }
  if (users === null) {
    return <p>Ładowanie…</p>
  }

  return (
    <div className="card">
      <h1>Użytkownicy ({users.length})</h1>
      <table className="table">
        <thead>
          <tr>
            <th>Imię i nazwisko</th>
            <th>E-mail</th>
            <th>Rola</th>
          </tr>
        </thead>
        <tbody>
          {users.map((user) => (
            <tr key={user.id}>
              <td>{playerName(user)}</td>
              <td>{user.email}</td>
              <td>{user.roles.includes('ROLE_ADMIN') ? 'Administrator' : 'Gracz'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
