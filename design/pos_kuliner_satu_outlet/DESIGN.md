---
version: alpha
name: POS Kuliner Satu Outlet
description: Desktop-first web application · Warm sand sidebar, crisp white cards
  on a cool off-white canvas, bold periwinkle primary actions, and clean tabular data
  hierarchy.
colors:
  primary: '#5E63E8'
  on-primary: '#FFFFFF'
  secondary: '#EFECE1'
  background: '#F8F8FA'
  surface: '#FFFFFF'
  on-surface: '#1E2029'
  on-surface-muted: '#8E92A4'
  outline: '#ECEEF2'
  error: '#E5484D'
  success: '#2EB872'
  scrim: '#1E2029'
  surface-dim: '#d9d9e6'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f2ff'
  surface-container: '#ededfa'
  surface-container-high: '#e7e7f4'
  surface-container-highest: '#e1e1ee'
  on-surface-variant: '#464554'
  inverse-surface: '#2e3039'
  inverse-on-surface: '#f0f0fc'
  outline-variant: '#c6c5d7'
  surface-tint: '#484cd2'
  primary-container: '#5e63e8'
  on-primary-container: '#fcf9ff'
  inverse-primary: '#c0c1ff'
  on-secondary: '#ffffff'
  secondary-container: '#e2e0d5'
  on-secondary-container: '#64635a'
  tertiary: '#8d4800'
  on-tertiary: '#ffffff'
  tertiary-container: '#b25c00'
  on-tertiary-container: '#fff9f7'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e1e0ff'
  primary-fixed-dim: '#c0c1ff'
  on-primary-fixed: '#04006d'
  on-primary-fixed-variant: '#2e30b9'
  secondary-fixed: '#e5e2d8'
  secondary-fixed-dim: '#c9c7bc'
  on-secondary-fixed: '#1c1c15'
  on-secondary-fixed-variant: '#48473f'
  tertiary-fixed: '#ffdcc5'
  tertiary-fixed-dim: '#ffb782'
  on-tertiary-fixed: '#301400'
  on-tertiary-fixed-variant: '#703800'
  on-background: '#191b24'
  surface-variant: '#e1e1ee'
  primary-pressed: '#4D52D6'
typography:
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: 700
    lineHeight: 1.25
    letterSpacing: -0.01em
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 15px
    fontWeight: 600
    lineHeight: 1.35
    letterSpacing: -0.005em
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: 500
    lineHeight: 1.5
    letterSpacing: 0em
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: 0em
  caption:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: 500
    lineHeight: 1.35
    letterSpacing: 0.01em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '700'
    lineHeight: 26px
    letterSpacing: -0.01em
rounded:
  sm: 6px
  md: 10px
  lg: 16px
  full: 9999px
  DEFAULT: 0.5rem
  xl: 1.5rem
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  gutter: 20px
  margin: 28px
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
components:
  button-primary:
    backgroundColor: '{colors.primary}'
    textColor: '{colors.on-primary}'
    rounded: '{rounded.md}'
    padding: 10px 16px
    typography: '{typography.label-md}'
  button-primary-pressed:
    backgroundColor: '#4D52D6'
    textColor: '{colors.on-primary}'
    rounded: '{rounded.md}'
    padding: 10px 16px
    typography: '{typography.label-md}'
  button-secondary:
    backgroundColor: '{colors.surface}'
    textColor: '{colors.on-surface}'
    rounded: '{rounded.md}'
    padding: 10px 16px
    typography: '{typography.label-md}'
  button-danger:
    backgroundColor: '{colors.error}'
    textColor: '{colors.on-primary}'
    rounded: '{rounded.md}'
    padding: 10px 16px
    typography: '{typography.label-md}'
  input:
    backgroundColor: '{colors.surface}'
    textColor: '{colors.on-surface}'
    borderColor: '{colors.outline}'
    rounded: '{rounded.md}'
    padding: 10px 14px
    typography: '{typography.body-md}'
  select:
    backgroundColor: '{colors.surface}'
    textColor: '{colors.on-surface}'
    borderColor: '{colors.outline}'
    rounded: '{rounded.md}'
    padding: 10px 14px
    typography: '{typography.body-md}'
  card:
    backgroundColor: '{colors.surface}'
    rounded: '{rounded.lg}'
    padding: 24px
  table:
    backgroundColor: '{colors.surface}'
    borderColor: '{colors.outline}'
    rounded: '{rounded.lg}'
    cellPadding: 14px 16px
    typography: '{typography.body-md}'
  badge:
    rounded: '{rounded.sm}'
    padding: 4px 8px
    typography: '{typography.caption}'
  tabs:
    activeColor: '{colors.primary}'
    inactiveColor: '{colors.on-surface-muted}'
    borderColor: '{colors.outline}'
    typography: '{typography.label-md}'
  modal-dialog:
    backgroundColor: '{colors.surface}'
    rounded: '{rounded.lg}'
    padding: 24px
  modal-scrim:
    backgroundColor: '{colors.scrim}'
  confirm-dialog:
    backgroundColor: '{colors.surface}'
    rounded: '{rounded.lg}'
    padding: 24px
---

