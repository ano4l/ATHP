"use client";

import React from "react";
import { cn } from "@/lib/utils";
import type { Section } from "@/app/page";
import {
  LayoutDashboard,
  FileText,
  CheckSquare,
  BarChart3,
  ChevronLeft,
  ChevronRight,
  ClipboardList,
  Settings,
  Receipt,
  CalendarDays,
  LogOut,
} from "lucide-react";

interface SidebarProps {
  activeSection: Section;
  onSectionChange: (section: Section) => void;
  collapsed: boolean;
  onCollapsedChange: (collapsed: boolean) => void;
  onLogout?: () => void;
}

const navItems: { id: Section; label: string; icon: React.ElementType; badge?: string }[] = [
  { id: "overview", label: "Overview", icon: LayoutDashboard },
  { id: "requisitions", label: "Requisitions", icon: FileText },
  { id: "approvals", label: "Approvals", icon: CheckSquare, badge: "4" },
  { id: "leaves", label: "Leave Management", icon: CalendarDays, badge: "2" },
  { id: "reports", label: "Reports", icon: BarChart3 },
  { id: "audit", label: "Audit Trail", icon: ClipboardList },
  { id: "settings", label: "Settings", icon: Settings },
];

export function Sidebar({
  activeSection,
  onSectionChange,
  collapsed,
  onCollapsedChange,
  onLogout,
}: SidebarProps) {
  return (
    <aside
      className={cn(
        "fixed left-0 top-0 z-40 h-screen bg-sidebar border-r border-sidebar-border transition-all duration-300 ease-out flex flex-col",
        collapsed ? "w-[72px]" : "w-[260px]"
      )}
    >
      <div className="h-16 flex items-center px-4 border-b border-sidebar-border">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 bg-white">
            <Receipt className="w-5 h-5 text-accent-foreground" />
          </div>
          <span
            className={cn(
              "font-semibold text-lg text-sidebar-foreground whitespace-nowrap transition-all duration-300",
              collapsed ? "opacity-0 w-0" : "opacity-100 w-auto"
            )}
          >
            AceTech
          </span>
        </div>
      </div>

      <nav className="flex-1 px-3 py-4 space-y-1 overflow-hidden">
        {navItems.map((item) => {
          const Icon = item.icon;
          const isActive = activeSection === item.id;

          return (
            <button
              key={item.id}
              onClick={() => onSectionChange(item.id)}
              className={cn(
                "w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group relative",
                isActive
                  ? "bg-sidebar-accent text-sidebar-foreground"
                  : "text-muted-foreground hover:text-sidebar-foreground hover:bg-sidebar-accent/50"
              )}
            >
              <span
                className={cn(
                  "absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-accent transition-all duration-300",
                  isActive ? "opacity-100" : "opacity-0"
                )}
              />
              <Icon
                className={cn(
                  "w-5 h-5 shrink-0 transition-transform duration-200",
                  isActive ? "text-accent" : "group-hover:scale-110"
                )}
              />
              <span
                className={cn(
                  "whitespace-nowrap transition-all duration-300 flex-1 text-left",
                  collapsed ? "opacity-0 w-0 overflow-hidden" : "opacity-100"
                )}
              >
                {item.label}
              </span>
              {item.badge && !collapsed && (
                <span className="ml-auto px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-accent text-accent-foreground leading-none">
                  {item.badge}
                </span>
              )}
            </button>
          );
        })}
      </nav>

      <div className="border-t border-sidebar-border">
        {!collapsed && (
          <div className="px-4 py-3 flex items-center gap-3">
            <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-accent/80 to-chart-1 flex items-center justify-center text-xs font-bold text-accent-foreground shrink-0">
              AU
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-xs font-semibold text-sidebar-foreground truncate">Admin User</p>
              <p className="text-[10px] text-muted-foreground truncate">admin@acetech.com</p>
            </div>
          </div>
        )}
        <div className="px-3 pb-3 flex items-center gap-2">
          <button
            onClick={() => onCollapsedChange(!collapsed)}
            className={cn(
              "flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm text-muted-foreground hover:text-sidebar-foreground hover:bg-sidebar-accent/50 transition-all duration-200",
              collapsed ? "w-full" : "flex-1"
            )}
            title={collapsed ? "Expand" : "Collapse"}
          >
            {collapsed ? <ChevronRight className="w-5 h-5" /> : <><ChevronLeft className="w-4 h-4" /><span>Collapse</span></>}
          </button>
          {onLogout && (
            <button
              onClick={onLogout}
              className="flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm text-destructive hover:text-destructive hover:bg-destructive/10 transition-all duration-200"
              title="Logout"
            >
              <LogOut className="w-4 h-4" />
              {!collapsed && <span>Logout</span>}
            </button>
          )}
        </div>
      </div>
    </aside>
  );
}
