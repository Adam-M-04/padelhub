import { useEffect, useState } from 'react'
import { apiFetch } from '../../api.js'
import { errorMessage } from '../../labels.js'

export default function AdminCourtsPage() {
  const [courts, setCourts] = useState(null)
  const [newName, setNewName] = useState('')
  const [editingId, setEditingId] = useState(null)
  const [editName, setEditName] = useState('')
  const [error, setError] = useState('')

  function loadCourts() {
    apiFetch('/admin/courts')
      .then(setCourts)
      .catch((e) => setError(errorMessage(e)))
  }

  useEffect(loadCourts, [])

  async function run(action) {
    setError('')
    try {
      await action()
      loadCourts()
      return true
    } catch (e) {
      setError(errorMessage(e))
      return false
    }
  }

  async function handleAdd(event) {
    event.preventDefault()
    const ok = await run(() => apiFetch('/admin/courts', { method: 'POST', body: { name: newName, active: true } }))
    if (ok) {
      setNewName('')
    }
  }

  async function handleSaveName(court) {
    const ok = await run(() => apiFetch(`/admin/courts/${court.id}`, { method: 'PUT', body: { name: editName, active: court.active } }))
    if (ok) {
      setEditingId(null)
    }
  }

  function handleToggle(court) {
    run(() => apiFetch(`/admin/courts/${court.id}`, { method: 'PUT', body: { name: court.name, active: !court.active } }))
  }

  function handleDelete(court) {
    if (window.confirm(`Usunąć kort „${court.name}”?`)) {
      run(() => apiFetch(`/admin/courts/${court.id}`, { method: 'DELETE' }))
    }
  }

  function startEditing(court) {
    setEditingId(court.id)
    setEditName(court.name)
  }

  if (courts === null) {
    return error ? <p className="error">{error}</p> : <p>Ładowanie…</p>
  }

  return (
    <div className="card">
      <h1>Korty</h1>

      <form className="inline-form" onSubmit={handleAdd}>
        <input value={newName} onChange={(e) => setNewName(e.target.value)} placeholder="Nazwa nowego kortu" required maxLength={100} />
        <button type="submit">Dodaj kort</button>
      </form>

      {error && <p className="error">{error}</p>}

      <table className="table">
        <thead>
          <tr>
            <th>Nazwa</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {courts.map((court) => (
            <tr key={court.id}>
              <td>
                {editingId === court.id ? (
                  <input value={editName} onChange={(e) => setEditName(e.target.value)} maxLength={100} aria-label="Nazwa kortu" />
                ) : (
                  court.name
                )}
              </td>
              <td>{court.active ? 'Aktywny' : 'Nieaktywny'}</td>
              <td className="actions">
                {editingId === court.id ? (
                  <>
                    <button type="button" onClick={() => handleSaveName(court)}>Zapisz</button>
                    <button type="button" className="button-light" onClick={() => setEditingId(null)}>Anuluj</button>
                  </>
                ) : (
                  <>
                    <button type="button" className="button-light" onClick={() => startEditing(court)}>Zmień nazwę</button>
                    <button type="button" className="button-light" onClick={() => handleToggle(court)}>
                      {court.active ? 'Dezaktywuj' : 'Aktywuj'}
                    </button>
                    <button type="button" className="button-danger" onClick={() => handleDelete(court)}>Usuń</button>
                  </>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
