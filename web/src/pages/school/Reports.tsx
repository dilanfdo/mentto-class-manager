import { useState } from 'react'
import { Card, ErrorBox, Input, Loading, PageHeader, Stat, Table } from '../../components/ui'
import { lkr, monthLabel, thisMonth } from '../../lib/format'
import { useApi } from '../../lib/useApi'

type Income = {
  month: string
  total: number
  by_method: Record<string, number>
  by_class: { class: string; total: number }[]
  outstanding: { student_id: number; name: string; class: string; amount: number }[]
  outstanding_total: number
}

const METHOD: Record<string, string> = { bank: 'Bank', cash: 'Cash', card: 'Card' }

export default function Reports() {
  const [month, setMonth] = useState(thisMonth())
  const { data, error, loading } = useApi<Income>(`/reports/income?month=${month}`)

  return (
    <>
      <PageHeader title="Reports" subtitle={monthLabel(month)} actions={<Input type="month" aria-label="Month" value={month} onChange={(e) => setMonth(e.target.value)} />} />
      {error && <ErrorBox message={error} />}
      {loading && !data ? <Loading /> : data && (
        <>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Stat label="Collected" value={lkr(data.total)} />
            <Stat label="Still owed" value={lkr(data.outstanding_total)} hint={`${data.outstanding.length} unpaid class fees`} />
            {Object.entries(data.by_method).map(([m, v]) => <Stat key={m} label={METHOD[m] ?? m} value={lkr(v)} />)}
          </div>
          <div className="mt-6 grid gap-6 lg:grid-cols-2">
            <Card>
              <h2 className="px-4 pt-4 font-semibold">By class</h2>
              <Table head={['Class', 'Collected']}>
                {data.by_class.map((c) => <tr key={c.class}><td className="px-4 py-2.5">{c.class}</td><td className="px-4 py-2.5 tabular-nums">{lkr(c.total)}</td></tr>)}
              </Table>
            </Card>
            <Card>
              <h2 className="px-4 pt-4 font-semibold">Who still owes</h2>
              <Table head={['Student', 'Class', 'Amount']}>
                {data.outstanding.map((o) => <tr key={`${o.student_id}-${o.class}`}><td className="px-4 py-2.5">{o.name}</td><td className="px-4 py-2.5 text-slate-600">{o.class}</td><td className="px-4 py-2.5 tabular-nums">{lkr(o.amount)}</td></tr>)}
                {data.outstanding.length === 0 && <tr><td colSpan={3} className="px-4 py-6 text-center text-slate-500">Everyone has paid.</td></tr>}
              </Table>
            </Card>
          </div>
        </>
      )}
    </>
  )
}
