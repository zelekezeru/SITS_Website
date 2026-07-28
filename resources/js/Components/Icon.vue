<script setup>
import { computed } from 'vue';
import {
  LayoutDashboard, Target, CalendarDays, Users, FileText, Building2, Gauge,
  ListChecks, Flag, Star, Sparkles, Award, Banknote, Clock, ReceiptText,
  Percent, PieChart, FolderOpen, ShieldCheck, KeyRound, Settings, UploadCloud,
  Search, Bell, ChevronDown, ChevronRight, Menu, X, LogOut, PanelLeftClose,
  PanelLeftOpen, CircleUserRound, Plus, ArrowRight, ArrowLeft, Sun, Moon,
  Command, Dot, CheckCircle2, AlertTriangle, CircleHelp, Trash2, Download,
  Globe, Newspaper,
  // Employee profile / credentials
  Eye, EyeOff, Mail, History, Briefcase, Hash, User, Image, Link2, File, Archive,
  // Finance / loans
  Landmark, Wallet, HandCoins, BookOpen, GraduationCap, Laptop,
  // Library
  LibraryBig, BookMarked, Bookmark, ScanLine, ScanBarcode, ArrowLeftRight,
  CreditCard, BarChart3, ClipboardCheck, Boxes, RotateCcw, ScrollText,
  Package, BookCopy, MapPin, BookPlus, Undo2, Layers, QrCode, BookText,
  // Payroll configuration & period actions
  SlidersHorizontal, ShieldAlert, CalendarX2, Calculator, Info, Loader2,
  RefreshCw, Sheet, Play,
  // Referenced across the app but previously unmapped — these silently fell back
  // to the Dot placeholder wherever they were used.
  Activity, BadgeCheck, BarChart2, Calendar, CalendarCheck, CalendarPlus,
  CalendarRange, CalendarX, Check, CheckCircle, ClipboardList, Compass, Cpu,
  ExternalLink, GitMerge, GitPullRequest, Receipt, Sigma, Upload, UserCheck,
  UserCircle, Weight, XCircle, Zap,
} from 'lucide-vue-next';

const map = {
  LayoutDashboard, Target, CalendarDays, Users, FileText, Building2, Gauge,
  ListChecks, Flag, Star, Sparkles, Award, Banknote, Clock, ReceiptText,
  Percent, PieChart, FolderOpen, ShieldCheck, KeyRound, Settings, UploadCloud,
  Search, Bell, ChevronDown, ChevronRight, Menu, X, LogOut, PanelLeftClose,
  PanelLeftOpen, CircleUserRound, Plus, ArrowRight, ArrowLeft, Sun, Moon,
  Command, Dot, CheckCircle2, AlertTriangle, CircleHelp, Trash2, Download,
  Globe, Newspaper,
  Eye, EyeOff, Mail, History, Briefcase, Hash, User, Image, Link2, File, Archive,
  Landmark, Wallet, HandCoins, BookOpen, GraduationCap, Laptop,
  LibraryBig, BookMarked, Bookmark, ScanLine, ScanBarcode, ArrowLeftRight,
  CreditCard, BarChart3, ClipboardCheck, Boxes, RotateCcw, ScrollText,
  Package, BookCopy, MapPin, BookPlus, Undo2, Layers, QrCode, BookText,
  // Payroll configuration & period actions
  SlidersHorizontal, ShieldAlert, CalendarX2, Calculator, Info, Loader2,
  RefreshCw, Sheet, Play,
  // Referenced across the app but previously unmapped — these silently fell back
  // to the Dot placeholder wherever they were used.
  Activity, BadgeCheck, BarChart2, Calendar, CalendarCheck, CalendarPlus,
  CalendarRange, CalendarX, Check, CheckCircle, ClipboardList, Compass, Cpu,
  ExternalLink, GitMerge, GitPullRequest, Receipt, Sigma, Upload, UserCheck,
  UserCircle, Weight, XCircle, Zap,
};

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 20 },
  strokeWidth: { type: [Number, String], default: 1.75 },
});

const component = computed(() => map[props.name] ?? Dot);
</script>

<template>
  <component :is="component" :size="Number(size)" :stroke-width="Number(strokeWidth)" />
</template>
