"use client";
import { useState } from "react";
import { Users, GraduationCap, School } from "lucide-react";
import { Link, useNavigate } from "react-router-dom";
import { useAuthStore } from "@/stores/use-auth-store";
import CmsFooter from "@/components/app-shell/CmsFooter";

type Role = "admin" | "faculty" | "student";

export default function LoginPage() {
    const [selectedRole, setSelectedRole] = useState<Role>("faculty");
    const [collegeId, setCollegeId] = useState("");
    const [password, setPassword] = useState("");
    const [formError, setFormError] = useState<string | null>(null);
    const navigate = useNavigate();
    const quickLogin = useAuthStore((state) => state.quickLogin);
    const validatePassword = (value: string) => {
        if (!/^[A-Z]/.test(value)) {
            return "Password must start with a capital letter.";
        }
        if (!/\d/.test(value)) {
            return "Password must include at least one number.";
        }
        if (!/[^A-Za-z0-9]/.test(value)) {
            return "Password must include at least one special character.";
        }
        return null;
    };
    const handleSignIn = (e: React.FormEvent) => {
        e.preventDefault();
        setFormError(null);
        const trimmedCollegeId = collegeId.trim();
        if (!trimmedCollegeId) {
            setFormError("College ID is required.");
            return;
        }
        const passwordError = validatePassword(password);
        if (passwordError) {
            setFormError(passwordError);
            return;
        }
        const roleForDb = selectedRole === "faculty" ? "teacher" : selectedRole;
        const offlineLogin = quickLogin(roleForDb, trimmedCollegeId);
        if (offlineLogin.user) {
            navigate(roleForDb === "student" ? "/attendance" : "/dashboard");
            return;
        }
        setFormError("Login failed. Please check your credentials.");
    };
    return (<div className="relative min-h-screen overflow-hidden bg-slate-950">
      <video className="absolute inset-0 h-full w-full object-cover" src="/videos/login-bg.mp4" autoPlay loop muted playsInline/>
      <div className="absolute inset-0 bg-[linear-gradient(135deg,rgba(4,15,37,0.86),rgba(15,55,128,0.64),rgba(255,255,255,0.18))]"/>
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.22),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,0.32),transparent_34%)]"/>

      <div className="relative z-10 flex min-h-screen items-center justify-center px-4 py-8 sm:px-6">
        <div className="w-full max-w-xl rounded-[2rem] border border-white/35 bg-white/90 p-6 shadow-[0_30px_90px_rgba(3,15,41,0.45)] backdrop-blur-xl sm:p-8 lg:p-10">
          <div className="mb-8 text-center">
            <h1 className="text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">Sign In</h1>
            <p className="mt-3 text-sm font-medium uppercase tracking-[0.28em] text-blue-700/80">
              Government Engineering College Baran
            </p>
          </div>

          <div className="mb-6 grid grid-cols-3 gap-3">
            <button type="button" onClick={() => setSelectedRole("admin")} className={`rounded-2xl border px-3 py-3 text-sm font-medium transition-all ${selectedRole === "admin"
            ? "border-blue-800 bg-blue-800 text-white shadow-[0_16px_40px_rgba(30,64,175,0.26)]"
            : "border-blue-200 bg-white text-slate-700 hover:border-blue-400 hover:bg-blue-50"}`}>
              <Users className="mx-auto mb-2 h-5 w-5" strokeWidth={selectedRole === "admin" ? 2.5 : 2}/>
              Admin
            </button>
            <button type="button" onClick={() => setSelectedRole("faculty")} className={`rounded-2xl border px-3 py-3 text-sm font-medium transition-all ${selectedRole === "faculty"
            ? "border-blue-800 bg-blue-800 text-white shadow-[0_16px_40px_rgba(30,64,175,0.26)]"
            : "border-blue-200 bg-white text-slate-700 hover:border-blue-400 hover:bg-blue-50"}`}>
              <GraduationCap className="mx-auto mb-2 h-5 w-5" strokeWidth={selectedRole === "faculty" ? 2.5 : 2}/>
              Faculty
            </button>
            <button type="button" onClick={() => setSelectedRole("student")} className={`rounded-2xl border px-3 py-3 text-sm font-medium transition-all ${selectedRole === "student"
            ? "border-blue-800 bg-blue-800 text-white shadow-[0_16px_40px_rgba(30,64,175,0.26)]"
            : "border-blue-200 bg-white text-slate-700 hover:border-blue-400 hover:bg-blue-50"}`}>
              <School className="mx-auto mb-2 h-5 w-5" strokeWidth={selectedRole === "student" ? 2.5 : 2}/>
              Student
            </button>
          </div>

          <form onSubmit={handleSignIn} className="space-y-4">
            <input type="text" value={collegeId} onChange={(e) => setCollegeId(e.target.value)} placeholder="College ID (e.g. 22EBRCS004)" className="w-full cursor-text rounded-2xl border border-blue-200 bg-white px-5 py-3 text-slate-950 caret-blue-700 shadow-[inset_0_1px_2px_rgba(15,23,42,0.06)] outline-none transition-all placeholder:text-slate-400 focus:border-blue-600 focus:bg-white focus:ring-4 focus:ring-blue-100" required autoComplete="username"/>
            <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="Password (First letter capital + 1 number + 1 special)" className="w-full cursor-text rounded-2xl border border-blue-200 bg-white px-5 py-3 text-slate-950 caret-blue-700 shadow-[inset_0_1px_2px_rgba(15,23,42,0.06)] outline-none transition-all placeholder:text-slate-400 focus:border-blue-600 focus:bg-white focus:ring-4 focus:ring-blue-100" required autoComplete="current-password"/>
            <p className="text-xs leading-relaxed text-slate-500">Password must start with a capital letter and include one number and one special character.</p>
            {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}
            <button type="submit" className="w-full rounded-2xl bg-gradient-to-r from-blue-800 to-blue-600 py-3.5 text-lg font-semibold text-white shadow-[0_18px_45px_rgba(30,64,175,0.28)] transition-all hover:from-blue-900 hover:to-blue-700">
              Sign In
            </button>
          </form>

          <div className="mt-6 text-center text-sm text-slate-600">
            Return to{" "}
            <Link to="/" className="font-semibold text-blue-800 hover:underline">
              Main Website
            </Link>
          </div>
        </div>
      </div>

      <CmsFooter variant="login" className="relative z-10 border-white/15 bg-slate-950/30 text-white"/>
    </div>);
}
