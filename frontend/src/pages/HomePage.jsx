import { useAuth } from '../auth/useAuth.js'

export default function HomePage() {
  const { user } = useAuth()

  return (
    <div className="card">
      <h1>Turnieje padlowe</h1>
      <p>{user ? `Witaj, ${user.firstName}!` : 'Zaloguj się, żeby zapisywać się na turnieje.'}</p>
    </div>
  )
}
