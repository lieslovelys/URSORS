#!/usr/bin/env python3
"""Compatibility entry point for the CSRF-aware integration checks."""
import runpy
from pathlib import Path
runpy.run_path(str(Path(__file__).with_name('ux_integration.py')), run_name='__main__')
