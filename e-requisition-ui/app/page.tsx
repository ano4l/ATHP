"use client";

import { useState, useEffect, useCallback } from "react";
import { Sidebar } from "@/components/dashboard/sidebar";
import { Header } from "@/components/dashboard/header";
import { OverviewSection } from "@/components/dashboard/sections/overview";
import { RequisitionsSection } from "@/components/dashboard/sections/requisitions";
import { ApprovalsSection } from "@/components/dashboard/sections/approvals";
import { LeavesSection } from "@/components/dashboard/sections/leaves";
import { ReportsSection } from "@/components/dashboard/sections/reports";
import { AuditSection } from "@/components/dashboard/sections/audit";
import { SettingsSection } from "@/components/dashboard/sections/settings";
import { logout } from "@/lib/api";

export type Section = "overview" | "requisitions" | "approvals" | "leaves" | "reports" | "audit" | "settings";

export default function Dashboard() {
  const [activeSection, setActiveSection] = useState<Section>("overview");
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [darkMode, setDarkMode] = useState(false);

  useEffect(() => {
    const isDark = document.documentElement.classList.contains("dark");
    setDarkMode(isDark);
  }, []);

  const toggleDarkMode = useCallback(() => {
    const next = !darkMode;
    setDarkMode(next);
    document.documentElement.classList.toggle("dark", next);
    localStorage.setItem("theme", next ? "dark" : "light");
  }, [darkMode]);

  const renderSection = () => {
    switch (activeSection) {
      case "overview":
        return <OverviewSection onNavigate={setActiveSection} />;
      case "requisitions":
        return <RequisitionsSection />;
      case "approvals":
        return <ApprovalsSection />;
      case "leaves":
        return <LeavesSection />;
      case "reports":
        return <ReportsSection />;
      case "audit":
        return <AuditSection />;
      case "settings":
        return <SettingsSection />;
      default:
        return <OverviewSection onNavigate={setActiveSection} />;
    }
  };

  const handleLogout = useCallback(async () => {
    await logout();
    window.location.href = "/";
  }, []);

  return (
    <div className="flex min-h-screen bg-background">
      <Sidebar
        activeSection={activeSection}
        onSectionChange={setActiveSection}
        collapsed={sidebarCollapsed}
        onCollapsedChange={setSidebarCollapsed}
        onLogout={handleLogout}
      />
      <div
        className={`flex-1 flex flex-col transition-all duration-300 ease-out ${
          sidebarCollapsed ? "ml-[72px]" : "ml-[260px]"
        }`}
      >
        <Header activeSection={activeSection} darkMode={darkMode} onToggleDarkMode={toggleDarkMode} />
        <main className="flex-1 p-6 overflow-auto">
          <div
            key={activeSection}
            className="animate-in fade-in slide-in-from-bottom-4 duration-500"
          >
            {renderSection()}
          </div>
        </main>
      </div>
    </div>
  );
}
