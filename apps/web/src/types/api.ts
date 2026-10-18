// Bentuk respons Laravel API v1 (lihat apps/core-api/app/Http/Resources/V1).

export type UserRole =
  | "super_admin"
  | "school_admin"
  | "principal"
  | "teacher"
  | "staff"
  | "student"
  | "guardian";

export interface SessionUser {
  id: number;
  name: string;
  username: string;
  email: string | null;
  role: UserRole;
  school_id: string | null;
}

export interface Employee {
  id: string;
  display_name: string;
  full_name: string;
  employee_number: string | null;
  position: string | null;
  employment_type: string;
  is_teacher: boolean;
  is_active: boolean;
}

export interface DepartmentPic {
  id: string;
  role: "lead" | "deputy" | "member" | "advisor";
  raci: "R" | "A" | "C" | "I";
  started_on: string;
  ended_on: string | null;
  employee?: Employee;
}

export interface Department {
  id: string;
  parent_id: string | null;
  code: string;
  name: string;
  color: string | null;
  is_active: boolean;
  children?: Department[];
  pics?: DepartmentPic[];
}

export interface AcademicYear {
  id: string;
  name: string;
  starts_on: string;
  ends_on: string;
  is_active: boolean;
  semesters?: { id: string; term: 1 | 2; label: string; is_active: boolean }[];
}

export interface School {
  id: string;
  npsn: string;
  name: string;
  level: string;
  city: string | null;
  province: string | null;
  timezone: string;
  settings: { kktp_default?: number; report_city?: string };
  principal?: Employee | null;
  active_academic_year?: AcademicYear | null;
}

export interface Resource<T> {
  data: T;
}
