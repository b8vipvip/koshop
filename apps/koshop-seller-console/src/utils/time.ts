const beijingDateTime = new Intl.DateTimeFormat('zh-CN', {
  timeZone: 'Asia/Shanghai',
  hour12: false,
  month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit',
})

export function formatBeijingDateTime(value: string | number | Date): string {
  const date = value instanceof Date ? value : new Date(value)
  return Number.isNaN(date.getTime()) ? String(value ?? '') : beijingDateTime.format(date)
}
