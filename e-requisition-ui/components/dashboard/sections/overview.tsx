"use client";

import { useState } from "react";
import { MetricCard } from "@/components/dashboard/metric-card";
import { RecentRequisitions } from "@/components/dashboard/recent-requisitions";
import { StatusChart } from "@/components/dashboard/charts/status-chart";
import { RequisitionForm } from "@/components/dashboard/modals/requisition-form";
import type { Section } from "@/app/page";
import { Clock, FileText, AlertTriangle, TrendingUp, Plus } from "lucide-react";

interface OverviewProps {
  onNavigate: (section: Section) => void;
}

export function OverviewSection({ onNavigate }: OverviewProps) {
  const [reqFormOpen, setReqFormOpen] = useState(false);

  return (
    <div className="space-y-6">
      <RequisitionForm open={reqFormOpen} onClose={() => setReqFormOpen(false)} />
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <MetricCard
          title="Total Requisitions"
          value="247"
          change="+18.3%"
          changeType="positive"
          icon={FileText}
          delay={0}
        />
        <MetricCard
          title="Pending Approvals"
          value="12"
          change="-3"
          changeType="positive"
          icon={Clock}
          delay={1}
        />
        <MetricCard
          title="Avg Turnaround"
          value="18h"
          change="-4h"
          changeType="positive"
          icon={TrendingUp}
          delay={3}
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <StatusChart />
        </div>
        <div className="space-y-4">
          <div className="bg-card border border-border rounded-xl p-5">
            <h3 className="text-base font-semibold text-foreground mb-3">Quick Actions</h3>
            <div className="space-y-2">
              {[
                { label: "New Requisition", icon: Plus, color: "text-chart-2", action: () => setReqFormOpen(true) },
                { label: "Pending Approvals (4)", icon: AlertTriangle, color: "text-warning", action: () => onNavigate("approvals") },
                { label: "View Reports", icon: TrendingUp, color: "text-muted-foreground", action: () => onNavigate("reports") },
              ].map((item) => (
                <button
                  key={item.label}
                  onClick={item.action}
                  className="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-secondary/50 transition-all duration-200 group"
                >
                  <div className="w-9 h-9 rounded-lg bg-secondary flex items-center justify-center group-hover:bg-accent/10 transition-colors">
                    <item.icon className={`w-4 h-4 ${item.color}`} />
                  </div>
                  <span className="text-sm font-medium text-foreground">{item.label}</span>
                </button>
              ))}
            </div>
          </div>

        </div>
      </div>

      <RecentRequisitions />
    </div>
  );
}
