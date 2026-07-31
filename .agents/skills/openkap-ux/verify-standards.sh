#!/usr/bin/env bash
# ============================================================
# OpenKap UI Standards Verification Script
# Scans the codebase for violations of the mandatory rules
# defined in .agents/skills/openkap-ux/SKILL.md
# ============================================================

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
FRONTEND="$PROJECT_ROOT/frontend/src"
VIOLATIONS=0
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m'

echo "============================================="
echo " OpenKap UI Standards Verification"
echo "============================================="
echo ""

# ----------------------------------------------------------
# Rule 1: Cards must use .card class
# ----------------------------------------------------------
echo -e "${YELLOW}Checking for inline card styling...${NC}"
RESULT=$(grep -rn "bg-white.*rounded-xl.*border.*border-gray-100\|bg-white.*border.*border-gray-100.*rounded-xl" "$FRONTEND/views/" "$FRONTEND/components/" 2>/dev/null | grep -v "echo\|\.card\|style\.css\|StandardsView\|//\s*card\s*→" || true)
if [ -n "$RESULT" ]; then
    echo "$RESULT" | while IFS= read -r line; do
        FILE=$(echo "$line" | cut -d: -f1)
        LINENO=$(echo "$line" | cut -d: -f2)
        echo -e "  ${RED}⚠  $FILE:$LINENO — inline card classes, use .card instead${NC}"
    done
    COUNT=$(echo "$RESULT" | wc -l | tr -d ' ')
    VIOLATIONS=$((VIOLATIONS + COUNT))
else
    echo -e "  ${GREEN}✓ No violations${NC}"
fi

# ----------------------------------------------------------
# Rule 2: No raw alert() calls
# ----------------------------------------------------------
echo ""
echo -e "${YELLOW}Checking for raw alert() calls...${NC}"
RESULT=$(grep -rn "\balert(" "$FRONTEND/" --include="*.vue" --include="*.js" 2>/dev/null | grep -v "toastService\|//.*alert\|/toast" || true)
if [ -n "$RESULT" ]; then
    echo "$RESULT" | while IFS= read -r line; do
        FILE=$(echo "$line" | cut -d: -f1)
        LINENO=$(echo "$line" | cut -d: -f2)
        echo -e "  ${RED}⚠  $FILE:$LINENO — raw alert(), use toast.error()/toast.success()${NC}"
    done
    COUNT=$(echo "$RESULT" | wc -l | tr -d ' ')
    VIOLATIONS=$((VIOLATIONS + COUNT))
else
    echo -e "  ${GREEN}✓ No violations${NC}"
fi

# ----------------------------------------------------------
# Rule 3: No inline Teleport modals (except SBModal/SBDropdown)
# ----------------------------------------------------------
echo ""
echo -e "${YELLOW}Checking for ad-hoc Teleport modals...${NC}"
RESULT=$(grep -rn "<Teleport to=\"body\">" "$FRONTEND/" --include="*.vue" 2>/dev/null | grep -v "SBModal" | grep -v "SBDropdown" | grep -v "StandardsView\|Teleport.*example\|<!--.*Teleport" || true)
if [ -n "$RESULT" ]; then
    echo "$RESULT" | while IFS= read -r line; do
        FILE=$(echo "$line" | cut -d: -f1)
        LINENO=$(echo "$line" | cut -d: -f2)
        echo -e "  ${RED}⚠  $FILE:$LINENO — inline Teleport, use SBModal or SBDropdown${NC}"
    done
    COUNT=$(echo "$RESULT" | wc -l | tr -d ' ')
    VIOLATIONS=$((VIOLATIONS + COUNT))
else
    echo -e "  ${GREEN}✓ No violations${NC}"
fi

# ----------------------------------------------------------
# Rule 4: Hover backgrounds must have padding
# ----------------------------------------------------------
echo ""
echo -e "${YELLOW}Checking for hover backgrounds missing horizontal padding...${NC}"
RESULT=$(grep -rn "hover:bg-gray-50\|hover:bg-gray-100\|hover:bg-orange-50\|hover:bg-red-50" "$FRONTEND/" --include="*.vue" 2>/dev/null | grep -v "px-\|p-[2-9]\|p-1[0-9]\|rounded-\|menu-item\|card\|group\|scope" | grep -v "StandardsView\|/\*\|style\.css" || true)
if [ -n "$RESULT" ]; then
    echo "$RESULT" | while IFS= read -r line; do
        FILE=$(echo "$line" | cut -d: -f1)
        LINENO=$(echo "$line" | cut -d: -f2)
        echo -e "  ${YELLOW}?  $FILE:$LINENO — hover bg may be missing px-* (review)${NC}"
    done
    COUNT=$(echo "$RESULT" | wc -l | tr -d ' ')
    echo -e "  ${YELLOW}  $COUNT items to review (may include legitimate <tr> rows)${NC}"
else
    echo -e "  ${GREEN}✓ No violations${NC}"
fi

# ----------------------------------------------------------
# Summary
# ----------------------------------------------------------
echo ""
echo "============================================="
if [ "$VIOLATIONS" -gt 0 ]; then
    echo -e " ${RED}$VIOLATIONS violations found${NC}"
    echo ""
    echo " Fix by following standards defined in:"
    echo "   file://$PROJECT_ROOT/.agents/skills/openkap-ux/SKILL.md"
    echo "   Live reference: /standards (admin-only)"
    exit 1
else
    echo -e " ${GREEN}All mandatory rules pass${NC}"
    echo ""
    echo " Live standards reference: /standards (admin-only)"
    exit 0
fi
