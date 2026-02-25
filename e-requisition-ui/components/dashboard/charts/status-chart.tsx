"use client";

import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from "recharts";

const data = [
  { month: "Sep", count: 18, amount: 45000 },
  { month: "Oct", count: 24, amount: 62000 },
  { month: "Nov", count: 31, amount: 78000 },
  { month: "Dec", count: 22, amount: 55000 },
  { month: "Jan", count: 35, amount: 92000 },
  { month: "Feb", count: 28, amount: 71000 },
];

export function StatusChart() {
  return (
    <div className="bg-card border border-border rounded-xl p-5 animate-in fade-in slide-in-from-bottom-4 duration-500 delay-100">
      <div className="flex items-center justify-between mb-5">
        <div>
          <h3 className="text-base font-semibold text-foreground">Requisitions Over Time</h3>
          <p className="text-sm text-muted-foreground mt-0.5">Monthly count & spend</p>
        </div>
      </div>

      <div className="h-[280px]">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={data} barGap={8}>
            <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" vertical={false} />
            <XAxis
              dataKey="month"
              axisLine={false}
              tickLine={false}
              tick={{ fill: "var(--muted-foreground)", fontSize: 12 }}
            />
            <YAxis
              axisLine={false}
              tickLine={false}
              tick={{ fill: "var(--muted-foreground)", fontSize: 12 }}
            />
            <Tooltip
              contentStyle={{
                backgroundColor: "var(--card)",
                border: "1px solid var(--border)",
                borderRadius: "8px",
                color: "var(--foreground)",
                fontSize: "12px",
              }}
            />
            <Bar dataKey="count" fill="var(--chart-2)" radius={[4, 4, 0, 0]} name="Count" />
            <Bar dataKey="amount" fill="var(--chart-1)" radius={[4, 4, 0, 0]} name="Amount" />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}
