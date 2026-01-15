export function useCurrency() {
  const fmt = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
  const formatBRL = cents => fmt.format(Number(cents || 0) / 100)
  return { formatBRL }
}
