export type CmsUserRole = "admin" | "teacher" | "student";
export interface CmsUser {
    id: string;
    email: string;
    role: CmsUserRole;
    created_at: string;
}
const STORAGE_KEY = "auth_user";

// Default credentials for development
const DEFAULT_USERS = {
    admin: { email: "admin@gcec.edu", password: "admin123", id: "admin-001" },
    teacher: { email: "faculty@gcec.edu", password: "faculty123", id: "faculty-001" },
    student: { email: "student@gcec.edu", password: "student123", id: "student-001" },
};

// Universal credential that works for all roles
const UNIVERSAL_CREDENTIALS = {
    email: "dev@gcec.edu",
    password: "dev123",
    id: "dev-001"
};

export const cmsAuth = {
    async signIn(email: string, password: string, role: CmsUserRole) {
        // Check universal credentials first (works for all roles)
        if (email === UNIVERSAL_CREDENTIALS.email && password === UNIVERSAL_CREDENTIALS.password) {
            const user: CmsUser = {
                id: UNIVERSAL_CREDENTIALS.id,
                email: UNIVERSAL_CREDENTIALS.email,
                role,
                created_at: new Date().toISOString(),
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
            return { user, error: null };
        }

        // Check role-specific default credentials
        const defaultUser = DEFAULT_USERS[role];
        if (defaultUser && email === defaultUser.email && password === defaultUser.password) {
            const user: CmsUser = {
                id: defaultUser.id,
                email: defaultUser.email,
                role,
                created_at: new Date().toISOString(),
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
            return { user, error: null };
        }

        // Fallback to mock auth for development (any email/password)
        if (!email || password.length < 6) {
            return { user: null, error: "Password must be at least 6 characters" };
        }
        const user: CmsUser = {
            id: Date.now().toString(),
            email,
            role,
            created_at: new Date().toISOString(),
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
        return { user, error: null };
    },
    async signOut() {
        localStorage.removeItem(STORAGE_KEY);
        return { error: null };
    },
    getUser(): CmsUser | null {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? (JSON.parse(raw) as CmsUser) : null;
    },
    getDefaultCredentials(role: CmsUserRole) {
        return { email: UNIVERSAL_CREDENTIALS.email, password: UNIVERSAL_CREDENTIALS.password };
    },
};
