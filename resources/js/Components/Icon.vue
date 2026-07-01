<script setup>
import { computed } from 'vue';
import {
  LayoutDashboard, Target, CalendarDays, Users, FileText, Building2, Gauge,
  ListChecks, Flag, Star, Sparkles, Award, Banknote, Clock, ReceiptText,
  Percent, PieChart, FolderOpen, ShieldCheck, KeyRound, Settings, UploadCloud,
  Search, Bell, ChevronDown, ChevronRight, Menu, X, LogOut, PanelLeftClose,
  PanelLeftOpen, CircleUserRound, Plus, ArrowRight, ArrowLeft, Sun, Moon,
  Command, Dot, CheckCircle2, AlertTriangle, CircleHelp, Trash2, Download,
  Globe,
  // Employee profile / credentials
  Eye, EyeOff, Mail, History, Briefcase, Hash, User, Image, Link2, File, Archive,
} from 'lucide-vue-next';

const map = {
  LayoutDashboard, Target, CalendarDays, Users, FileText, Building2, Gauge,
  ListChecks, Flag, Star, Sparkles, Award, Banknote, Clock, ReceiptText,
  Percent, PieChart, FolderOpen, ShieldCheck, KeyRound, Settings, UploadCloud,
  Search, Bell, ChevronDown, ChevronRight, Menu, X, LogOut, PanelLeftClose,
  PanelLeftOpen, CircleUserRound, Plus, ArrowRight, ArrowLeft, Sun, Moon,
  Command, Dot, CheckCircle2, AlertTriangle, CircleHelp, Trash2, Download,
  Globe,
  Eye, EyeOff, Mail, History, Briefcase, Hash, User, Image, Link2, File, Archive,
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
