export const formatLabels = {
  AMERICANO: 'Americano',
  MEXICANO: 'Mexicano',
}

export const statusLabels = {
  DRAFT: 'Szkic',
  OPEN: 'Zapisy otwarte',
  IN_PROGRESS: 'W trakcie',
  FINISHED: 'Zakończony',
}

export function formatDate(value) {
  return new Date(value).toLocaleString('pl-PL', { dateStyle: 'medium', timeStyle: 'short' })
}

export function playerName(player) {
  return `${player.firstName} ${player.lastName}`
}

export function teamNames(team) {
  return team.map(playerName).join(' i ')
}

const errorMessages = {
  NETWORK_ERROR: 'Brak połączenia z serwerem.',
  COURT_NAME_TAKEN: 'Kort o tej nazwie już istnieje.',
  COURT_IN_USE: 'Kort jest używany w turniejach. Zamiast go usuwać, dezaktywuj go.',
  COURT_INACTIVE: 'Do turnieju przypisano nieaktywny kort.',
  TOURNAMENT_NOT_EDITABLE: 'Turniej można edytować tylko przed startem.',
  TOURNAMENT_NOT_DRAFT: 'Ta operacja jest możliwa tylko dla szkicu.',
  TOURNAMENT_NOT_OPEN: 'Rozpocząć można tylko turniej z otwartymi zapisami.',
  TOURNAMENT_NOT_IN_PROGRESS: 'Turniej nie jest w trakcie.',
  INVALID_PLAYER_COUNT: 'Liczba zapisanych graczy musi być podzielna przez 4.',
  ROUND_NOT_FINISHED: 'Wszystkie mecze bieżącej rundy muszą mieć wynik.',
}

export function errorMessage(error) {
  return errorMessages[error.code] ?? 'Coś poszło nie tak. Spróbuj ponownie.'
}

export function fieldErrors(error) {
  const errors = {}
  if (error.code === 'VALIDATION_ERROR') {
    for (const detail of error.details) {
      errors[detail.field] = detail.message
    }
  }
  return errors
}
