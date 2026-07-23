# SYSTEM REQUIREMENTS SPECIFICATION (SRS)
## Medication Reconciliation Prototype System
### SASMEC @IIUM

**Document Version:** 1.0  
**Date:** June 2026  
**Status:** Requirements Definition  
**Project Type:** Prototype Development (Standalone System)

---

## TABLE OF CONTENTS

1. Introduction
2. System Overview
3. Functional Requirements by Module
4. Non-Functional Requirements
5. Data Model & Database Schema
6. Use Cases
7. User Roles & Permissions
8. System Architecture
9. Technical Stack Recommendations
10. Interface Requirements
11. Performance & Scalability
12. Security & Compliance
13. Testing Requirements
14. Deployment & Operations

---

## 1. INTRODUCTION

### 1.1 Purpose

This System Requirements Specification (SRS) document defines the complete functional and non-functional requirements for a standalone medication reconciliation prototype system for SASMEC @IIUM. This prototype will operate independently without integration with existing hospital systems (PhIS, EHR, or admission systems).

### 1.2 Scope

**In Scope:**
- Complete medication reconciliation workflow (admission to discharge)
- Patient and medication data management
- Discrepancy identification and resolution
- Reporting and quality metrics
- User management and authentication

**Out of Scope:**
- Integration with external systems (PhIS, EHR, hospital admission systems)
- Bi-directional data synchronization with other systems
- Real-time clinical decision support from external databases
- Patient portal or public-facing interfaces
- Billing or financial management functions

### 1.3 Intended Users

- **Pharmacy Technicians:** Data collection and BPMH compilation (primary user)
- **Clinical Pharmacists:** Verification, clinical assessment, prescriber communication
- **Ward Nurses:** Support and information retrieval
- **Physicians/Medical Officers:** Review and approval of recommendations
- **Pharmacy Manager/QI Team:** Metrics, reporting, and performance monitoring

### 1.4 Constraints

- Prototype phase: Not production-scale initially
- Standalone operation: No external system dependencies
- Offline capability: Should function with limited connectivity
- Manual data entry: No automated data import from external sources
- Training requirement: Moderate training needed (2-3 hours per user)

---

## 2. SYSTEM OVERVIEW

### 2.1 High-Level System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    WEB APPLICATION FRONTEND                 │
│        (React/Angular - Responsive, Multi-device)           │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION SERVER LAYER                  │
│         (Node.js/Express or Python/Flask/Django)            │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ • Authentication & Authorization Service             │   │
│  │ • Patient Management Service                         │   │
│  │ • Medication History Service                         │   │
│  │ • Reconciliation Engine Service                      │   │
│  │ • Clinical Decision Support Service                  │   │
│  │ • Reporting & Analytics Service                      │   │
│  │ • User Management Service                            │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                    DATA ACCESS LAYER                         │
│         (ORM Framework: Sequelize, TypeORM, SQLAlchemy)     │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                    DATABASE LAYER                            │
│      (PostgreSQL/MySQL - Relational Database)              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ • Patients                    • Medications          │   │
│  │ • Reconciliation Records      • Discrepancies        │   │
│  │ • Medication History          • Clinical Notes       │   │
│  │ • Users & Roles               • Audit Logs           │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│              LOCAL STORAGE / FILE SYSTEM                     │
│  (For offline capability, backup, export functionality)     │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Core System Modules

| Module | Primary Function | Primary Users |
|--------|-----------------|---------------|
| **Patient Management** | Register and manage patient data | All users |
| **Medication History** | Collect and compile medication information | Technician, Pharmacist |
| **Reconciliation Engine** | Identify and manage medication discrepancies | Pharmacist |
| **Intervention Management** | Document and communicate recommendations | Pharmacist, Physician |
| **Reporting & Analytics** | Generate quality metrics and dashboards | Manager, QI Team |
| **User Management** | Authentication and access control | System Administrator |

---

## 3. FUNCTIONAL REQUIREMENTS BY MODULE

### 3.1 MODULE 1: PATIENT MANAGEMENT

#### 3.1.1 Patient Registration (FR-PM-001)

**Requirement:** System shall allow creation and management of patient records.

**Functional Requirements:**

```
FR-PM-001.1 Create Patient Record
├─ System shall capture following required fields:
│  ├─ Patient ID (auto-generated or manual entry)
│  ├─ Full Name (First, Middle, Last)
│  ├─ Date of Birth
│  ├─ Gender (Male/Female/Other)
│  ├─ Contact Number (primary and secondary)
│  ├─ Email Address
│  ├─ Address (Street, City, Postcode, State)
│  ├─ Admission Date & Time
│  ├─ Ward/Department
│  ├─ Primary Diagnosis (ICD-10 code or text)
│  ├─ Known Allergies/Adverse Drug Reactions (ADRs)
│  ├─ Renal Function Status (eGFR/Creatinine clearance)
│  ├─ Hepatic Function Status
│  ├─ Pregnancy Status (if female)
│  └─ Special Notes/Precautions
│
├─ System shall validate:
│  ├─ Mandatory field completion
│  ├─ Date format (DD/MM/YYYY)
│  ├─ Contact number format (Malaysia +60 or local format)
│  ├─ Email format (valid email syntax)
│  ├─ Patient ID uniqueness (no duplicates)
│  └─ Date of birth not in future
│
├─ System shall provide:
│  ├─ Auto-calculation of patient age
│  ├─ Auto-flagging of high-risk patients (age >65, >5 medications, etc.)
│  └─ Warning if similar patient name already exists (duplicate check)
│
└─ Acceptance Criteria:
   ├─ Patient record created successfully
   ├─ All required fields saved to database
   ├─ Validation errors displayed with clear messages
   └─ Record is searchable immediately after creation

FR-PM-001.2 Patient Search & Retrieval
├─ System shall support search by:
│  ├─ Patient ID (exact match)
│  ├─ Full Name (partial name matching)
│  ├─ Date of Birth
│  ├─ Admission Date (range search)
│  └─ Ward/Department
│
├─ System shall return:
│  ├─ Search results in paginated list (10 results per page)
│  ├─ Patient ID, Name, DOB, Admission Date in result view
│  ├─ Sorting options (by ID, Name, Admission Date)
│  └─ Quick-view summary card on hover
│
└─ Response time requirement: <2 seconds for search

FR-PM-001.3 Edit Patient Record
├─ Authorized users (Pharmacist, Admin) may edit:
│  ├─ Contact information
│  ├─ Allergies/ADRs
│  ├─ Clinical notes
│  └─ Ward/Department assignment (if transferred)
│
├─ System shall NOT allow edit of:
│  ├─ Patient ID
│  ├─ Date of Birth (unless data correction required - audit trail mandatory)
│  ├─ Admission Date (unless correction - audit trail mandatory)
│  └─ Gender (unless correction - audit trail mandatory)
│
├─ System shall maintain:
│  ├─ Audit trail of all edits (who, when, what changed)
│  ├─ Before and after values
│  └─ Reason for change (if sensitive data)
│
└─ Acceptance: Edit saved, audit log created, confirmation displayed

FR-PM-001.4 Patient Discharge / Close Record
├─ System shall:
│  ├─ Allow designation of patient as discharged
│  ├─ Capture discharge date and time
│  ├─ Archive patient record (not delete)
│  ├─ Generate discharge summary report
│  └─ Final reconciliation status marked as complete
│
├─ System shall prevent:
│  ├─ Further medication additions post-discharge
│  ├─ New discrepancies added post-discharge
│  └─ Accidental deletion of discharge data
│
└─ Archived records remain searchable and reportable
```

#### 3.1.2 Patient Risk Stratification (FR-PM-002)

**Requirement:** System shall automatically flag high-risk patients requiring priority reconciliation.

```
FR-PM-002.1 Automatic Risk Assessment
├─ System shall calculate risk score based on:
│  ├─ Age >65 years → HIGH RISK
│  ├─ >5 home medications → HIGH RISK
│  ├─ Renal impairment (eGFR <60) → HIGH RISK
│  ├─ Hepatic impairment → HIGH RISK
│  ├─ Pregnancy status → HIGH RISK
│  ├─ History of medication non-adherence → MEDIUM RISK
│  ├─ Recent hospitalization (<30 days) → MEDIUM RISK
│  ├─ Multiple drug allergies (>3) → MEDIUM RISK
│  └─ Complex comorbidities (>3 chronic diseases) → MEDIUM RISK
│
├─ Risk Level Assignment:
│  ├─ HIGH: Multiple high-risk factors or ≥2 drug allergies + age >65
│  ├─ MEDIUM: 1-2 medium-risk factors or mixed factors
│  └─ LOW: No risk factors
│
├─ Visual Indicators:
│  ├─ Color-coded badges (RED=HIGH, YELLOW=MEDIUM, GREEN=LOW)
│  ├─ Display on patient list and patient detail view
│  └─ Sort/filter by risk level on dashboard
│
└─ Alert System:
   ├─ HIGH-RISK patients appear at top of pharmacy worklist
   └─ Notification sent when HIGH-RISK patient admitted (optional)
```

---

### 3.2 MODULE 2: MEDICATION HISTORY COLLECTION

#### 3.2.1 Bedside Interview Data Collection (FR-MH-001)

**Requirement:** System shall provide structured interface for pharmacy technician to collect comprehensive medication history.

```
FR-MH-001.1 Interview Initiation
├─ System shall:
│  ├─ Load patient record automatically (by patient ID or search)
│  ├─ Display patient demographics and allergies prominently
│  ├─ Present allergy warnings (visual banner)
│  ├─ Show clinical context (admission diagnosis, ward)
│  ├─ Record interview start time automatically
│  └─ Assign technician/user to interview
│
└─ System shall prevent:
   └─ Starting interview for patient with incomplete demographic data

FR-MH-001.2 Current Medication Listing
├─ System shall provide structured form with fields:
│  ├─ Medication Name (text input with autocomplete from drug database)
│  ├─ Strength/Dosage (e.g., 500mg, 10 IU)
│  ├─ Dose Amount (quantity per administration)
│  ├─ Route (dropdown: PO, IV, IM, SC, Topical, Inhaled, etc.)
│  ├─ Frequency (dropdown: Daily, BID, TID, QID, Weekly, Monthly, PRN, etc.)
│  ├─ Duration/Timing (e.g., morning, evening, with meals)
│  ├─ Indication (clinical reason for medication)
│  ├─ Start Date (when patient began taking)
│  ├─ Prescribing Provider (doctor/clinic name)
│  ├─ Is Patient Currently Taking? (Yes/No/Not Sure)
│  ├─ Patient Adherence (Full/Partial/None - if not taking)
│  ├─ Reason for Non-Adherence (if applicable)
│  └─ Source of Information (Patient report/Family/Medication bottle/Previous record)
│
├─ Data Entry Features:
│  ├─ Add medication button (repeating form for multiple medications)
│  ├─ Delete row functionality (with confirmation)
│  ├─ Duplicate detection (warn if same medication entered twice)
│  ├─ Medication database autocomplete (top 200 common medications in Malaysia)
│  ├─ Copy from previous admission (if available)
│  └─ Batch import from medication list (if available)
│
├─ Validation Rules:
│  ├─ Medication name is required
│  ├─ Dose amount must be numeric
│  ├─ Route must be selected
│  ├─ Frequency must be selected
│  ├─ Warn if duration/timing not specified
│  ├─ Warn if indication/purpose not provided
│  └─ Error if patient non-adherence reason blank when "Not Taking" selected
│
└─ User Experience:
   ├─ Intuitive form layout (one medication per row in table)
   ├─ Real-time validation with inline error messages
   ├─ Progress indicator (X of Y medications completed)
   └─ Ability to save as draft and resume later

FR-MH-001.3 Over-the-Counter & Supplementary Medications
├─ System shall explicitly collect:
│  ├─ Over-the-counter medications (headache relief, antacids, etc.)
│  ├─ Herbal/Traditional medications (tongkat ali, ginseng, etc.)
│  ├─ Nutritional supplements (vitamins, minerals)
│  ├─ Dietary supplements (probiotics, omega-3, etc.)
│  ├─ Weight loss products
│  └─ Other non-prescription remedies
│
├─ Data Fields:
│  ├─ Product name
│  ├─ Active ingredients (if known)
│  ├─ Dosage and frequency
│  ├─ Duration of use
│  ├─ Reason for use
│  └─ Purchased from (pharmacy, shop, online, etc.)
│
└─ System shall:
   ├─ Flag herbal/supplement interactions with medications
   ├─ Store separately for clinical review
   └─ Include in reconciliation process
```

#### 3.2.2 Best Possible Medication History Compilation (FR-MH-002)

**Requirement:** System shall compile BPMH from multiple interview and source information.

```
FR-MH-002.1 BPMH Compilation Process
├─ System shall aggregate information from:
│  ├─ Current bedside interview (primary source)
│  ├─ Patient's previous medication list (if available)
│  ├─ Previous hospital discharge summaries (manual entry)
│  ├─ Outpatient clinic records (manual entry)
│  ├─ External pharmacy information (manual entry)
│  └─ Family/caregiver input (documented in interview)
│
├─ Conflict Resolution Logic:
│  ├─ IF discrepancy between sources:
│  │  ├─ Flag for pharmacist review
│  │  ├─ Mark source with discrepancy notation
│  │  └─ Display all versions for comparison
│  │
│  ├─ IF medication in previous list but NOT in current interview:
│  │  ├─ Mark as "MISSING - Requires clarification"
│  │  └─ Flag for pharmacist verification
│  │
│  └─ IF medication in current interview but NOT in previous:
│     ├─ Mark as "NEW - Needs verification"
│     └─ Flag for appropriateness assessment
│
├─ BPMH Compilation Output:
│  ├─ Generate consolidated medication list
│  ├─ Each medication with data source indicators
│  ├─ Conflicting information highlighted
│  ├─ Confidence level assigned (HIGH/MEDIUM/LOW)
│  ├─ Timestamp of compilation
│  ├─ Technician who compiled BPMH
│  └─ Ready for pharmacist verification
│
└─ System shall provide:
   ├─ Print/export of BPMH for physical file
   ├─ Summary statistics (# medications, # sources consulted)
   └─ List of medications needing clarification/verification
```

#### 3.2.3 Source Documentation (FR-MH-003)

**Requirement:** System shall document and track all sources of medication information.

```
FR-MH-003.1 Source Tracking
├─ For each medication, system shall record:
│  ├─ Primary Source:
│  │  ├─ Patient/Family verbal report
│  │  ├─ Medication bottles/containers
│  │  ├─ Previous discharge summary
│  │  ├─ Outpatient pharmacy record
│  │  ├─ Hospital previous admission
│  │  └─ Other (specify)
│  │
│  ├─ Source Details:
│  │  ├─ Date information obtained
│  │  ├─ Person providing information (if not patient)
│  │  ├─ Verification method (phone call, letter, viewed original, etc.)
│  │  └─ Reliability rating (Definite/Probable/Possible)
│  │
│  └─ Documentation:
│     ├─ User recording source
│     ├─ Timestamp
│     └─ Any notes about source reliability

FR-MH-003.2 Source Comparison Interface
├─ System shall display side-by-side comparison of:
│  ├─ Patient-reported medications
│  ├─ Previous hospital records
│  ├─ Outpatient clinic documentation
│  ├─ Previous discharge summary
│  └─ External pharmacy records
│
├─ Visual Indicators:
│  ├─ Match status (identical, dose difference, frequency difference, etc.)
│  ├─ Source reliability color-coding
│  ├─ Discrepancy highlighting
│  └─ Notes/clarifications displayed
│
└─ Pharmacist Action:
   ├─ Select which version is "Best Possible"
   ├─ Document rationale for selection
   └─ Flag if conflicts unresolved
```

---

### 3.3 MODULE 3: RECONCILIATION ENGINE

#### 3.3.1 Medication List Comparison (FR-RE-001)

**Requirement:** System shall compare BPMH against current/intended medication list and identify discrepancies.

```
FR-RE-001.1 Admission Reconciliation Setup
├─ System shall:
│  ├─ Display BPMH (compiled by technician)
│  ├─ Provide interface for entry of current/intended medications
│  ├─ Allow pharmacist to input medications from chart/order entry
│  ├─ Display both lists side-by-side for comparison
│  ├─ Automatically identify matching and non-matching medications
│  └─ Highlight discrepancies prominently
│
├─ Data Input for Current Medications:
│  ├─ Medication name
│  ├─ Dose
│  ├─ Route
│  ├─ Frequency
│  ├─ Indication
│  └─ Ordered by (physician/hospital)
│
└─ System shall support:
   ├─ Manual entry of each medication
   ├─ Copy from BPMH for unchanged medications
   ├─ Modification of BPMH data if needed
   └─ Addition of new medications not in BPMH

FR-RE-001.2 Automatic Discrepancy Detection
├─ System shall automatically identify and flag:
│  │
│  ├─ OMISSION: In BPMH but NOT in current list
│  │  ├─ Medication was patient taking, now not prescribed
│  │  ├─ Potential unintended discontinuation
│  │  └─ Requires pharmacist clarification
│  │
│  ├─ COMMISSION: In current list but NOT in BPMH
│  │  ├─ New medication not previously taken
│  │  ├─ Potential new addition
│  │  └─ Requires appropriateness assessment
│  │
│  ├─ DOSE CHANGE: Same medication, different dose
│  │  ├─ Example: Patient taking 500mg, now prescribed 1000mg
│  │  ├─ Flag if increase >50% or decrease >25%
│  │  └─ May be intentional dose adjustment
│  │
│  ├─ FREQUENCY CHANGE: Same medication, different frequency
│  │  ├─ Example: Patient taking daily, now prescribed BID
│  │  ├─ May be clinical adjustment
│  │  └─ Requires verification
│  │
│  ├─ ROUTE CHANGE: Same medication, different route
│  │  ├─ Example: Patient taking oral, now IV
│  │  └─ May be intentional; verify appropriateness
│  │
│  ├─ DUPLICATE: Same/similar medications in list
│  │  ├─ Example: Acetaminophen + Paracetamol (same drug, different name)
│  │  ├─ Example: Two different dosages of same drug
│  │  └─ Flag for clinical review
│  │
│  └─ THERAPEUTIC DUPLICATION: Different medications, same therapeutic class
│     ├─ Example: Two ACE inhibitors or two statins
│     ├─ May be intentional combination; requires verification
│     └─ Database of therapeutic classes needed

FR-RE-001.3 Discrepancy Classification
├─ System shall classify each discrepancy as:
│  │
│  ├─ TYPE (from list above)
│  ├─ SEVERITY:
│  │  ├─ CRITICAL: Potential for serious harm
│  │  ├─ MAJOR: Potential for moderate harm
│  │  ├─ MINOR: Minimal potential for harm
│  │  └─ DOCUMENTATION: Data quality issue only
│  │
│  ├─ CLINICAL SIGNIFICANCE:
│  │  ├─ High: Medication with narrow therapeutic index (warfarin, digoxin)
│  │  ├─ Moderate: Common medication with potential interactions
│  │  ├─ Low: Routine medication, low interaction risk
│  │  └─ Unknown: Requires pharmacist assessment
│  │
│  ├─ CATEGORY:
│  │  ├─ Unintended discrepancy (medication error/omission)
│  │  ├─ Intentional discrepancy (deliberate discontinuation/change)
│  │  └─ Requires clarification (uncertain)
│  │
│  └─ STATUS:
│     ├─ Identified
│     ├─ Under Review
│     ├─ Resolved
│     └─ Pending Prescriber Action

FR-RE-001.4 Discrepancy Summary Report
├─ System shall generate summary showing:
│  ├─ Total number of discrepancies identified
│  ├─ Count by type (omissions, duplications, dose changes, etc.)
│  ├─ Count by severity (critical, major, minor)
│  ├─ Medications requiring action
│  ├─ Medications OK/no action needed
│  └─ Medications needing clarification
│
└─ Report provides basis for pharmacist clinical review
```

#### 3.3.2 Clinical Decision Support (FR-RE-002)

**Requirement:** System shall provide clinical decision support for medication appropriateness assessment.

```
FR-RE-002.1 Drug-Drug Interaction Screening
├─ System shall:
│  ├─ Maintain database of common drug-drug interactions
│  ├─ Check each medication against all others
│  ├─ Flag identified interactions
│  ├─ Display interaction severity (Contraindicated/Severe/Moderate/Minor)
│  ├─ Provide mechanism of interaction
│  ├─ Suggest monitoring or management strategies
│  └─ Allow pharmacist to acknowledge or override
│
├─ Interaction Database:
│  ├─ Include top 100-150 commonly prescribed medications in Malaysia
│  ├─ Include critical interactions (warfarin, NSAIDs, etc.)
│  ├─ Include herbal-drug interactions
│  ├─ Define severity levels with clear criteria
│  └─ Include recommended actions
│
├─ Display:
│  ├─ Interaction matrix/grid
│  ├─ Severity color-coding (Red=Contraindicated, Orange=Severe, Yellow=Moderate)
│  ├─ Quick reference card with recommendations
│  ├─ Link to more detailed information
│  └─ Documented by authoritative source (Micromedex, reference)
│
└─ System shall NOT block prescribing, but:
   ├─ Flag to pharmacist and physician
   ├─ Require documented acknowledgment
   ├─ Document decision rationale if interaction accepted
   └─ Include in clinical notes for future reference

FR-RE-002.2 Drug-Disease Contraindications
├─ System shall:
│  ├─ Maintain database of drug-disease contraindications
│  ├─ Check medications against patient's diagnosis/conditions
│  ├─ Flag contraindications (Absolute/Relative)
│  ├─ Explain clinical rationale
│  ├─ Suggest alternatives if available
│  └─ Allow pharmacist to document assessment
│
├─ Common Contraindications:
│  ├─ NSAIDs in renal impairment
│  ├─ ACE inhibitors in pregnancy
│  ├─ Metformin in renal impairment
│  ├─ Anticholinergics in angle-closure glaucoma
│  └─ Others (specific to Malaysian context)
│
└─ Assessment Interface:
   ├─ Contraindication identified with rationale
   ├─ Clinical context from patient record (renal function, diagnosis)
   ├─ Pharmacist assessment checkbox (Accepted/Requires change/Override documented)
   └─ If override, mandatory documentation of rationale

FR-RE-002.3 Dosage Appropriateness
├─ System shall:
│  ├─ Check doses against renal function (if eGFR available)
│  ├─ Check doses against hepatic function (if available)
│  ├─ Check doses for special populations (elderly, pregnancy, pediatric)
│  ├─ Flag doses outside normal range
│  ├─ Suggest dose adjustments if needed
│  └─ Provide reference ranges
│
├─ Dosage Assessment:
│  ├─ Standard dose
│  ├─ Renal-adjusted dose (if eGFR <60)
│  ├─ Hepatic-adjusted dose (if impairment present)
│  ├─ Elderly dose adjustment (if age >65)
│  └─ Pregnancy category (if applicable)
│
├─ Display:
│  ├─ Current prescribed dose
│  ├─ Recommended dose (if different)
│  ├─ Dose frequency appropriateness
│  ├─ Maximum daily dose check
│  └─ Clinical context for adjustment

FR-RE-002.4 Allergy & Adverse Reaction Cross-Check
├─ System shall:
│  ├─ Maintain patient's documented allergies/ADRs
│  ├─ Check each medication against allergy list
│  ├─ Flag cross-reactive medications (e.g., cephalosporins with penicillin allergy)
│  ├─ Display allergy type (anaphylaxis, rash, GI upset, etc.)
│  ├─ Highlight severity (life-threatening, serious, mild)
│  └─ Suggest alternatives if available
│
├─ Allergy Reaction Types:
│  ├─ True allergy (immune-mediated)
│  ├─ Intolerance (non-immune adverse reaction)
│  ├─ Drug sensitivity
│  └─ Side effect (expected adverse effect)
│
├─ Cross-Reactivity Alerts:
│  ├─ Penicillin → Cephalosporin (relative risk)
│  ├─ Sulfonamides (risk of cross-reactivity)
│  ├─ NSAIDs (in aspirin sensitivity)
│  └─ Others (documented in database)
│
└─ System shall:
   ├─ Display prominent ALLERGY WARNING banner
   ├─ Prevent inadvertent prescribing of cross-reactive medications
   ├─ Require explicit pharmacist/physician override if prescribed
   └─ Document clinical rationale for override
```

---

### 3.4 MODULE 4: INTERVENTION MANAGEMENT & RESOLUTION

#### 3.4.1 Pharmacist Assessment & Recommendations (FR-INT-001)

**Requirement:** System shall provide structured interface for pharmacist to assess discrepancies and formulate recommendations.

```
FR-INT-001.1 Pharmacist Clinical Assessment
├─ System shall display:
│  ├─ Patient summary (demographics, diagnosis, allergies)
│  ├─ BPMH compiled by technician
│  ├─ Current/intended medication list
│  ├─ All identified discrepancies (with severity flagging)
│  ├─ Clinical decision support alerts
│  ├─ Available clinical context (renal function, diagnosis, etc.)
│  └─ Previous medication reconciliations (if available)
│
├─ Pharmacist Actions:
│  ├─ Review each discrepancy
│  ├─ Assess clinical significance
│  ├─ Determine if unintended or intentional
│  ├─ Document clinical reasoning
│  ├─ Formulate recommendation (if action needed)
│  ├─ Identify medications for discussion with physician
│  └─ Proceed to communication phase
│
└─ Documentation Fields (for each discrepancy):
   ├─ Pharmacist assessment (checkbox: Unintended/Intentional/Requires Clarification)
   ├─ Clinical significance rating (High/Moderate/Low/Already Addressed)
   ├─ Clinical rationale (free text notes)
   ├─ Recommended action (ADD/DELETE/MODIFY DOSE/MODIFY FREQUENCY/NO ACTION)
   ├─ Evidence or reference for recommendation
   ├─ Alternative drugs suggested (if applicable)
   └─ Timestamp and pharmacist signature (digital)

FR-INT-001.2 Recommendation Formulation
├─ For each identified unintended discrepancy, pharmacist may recommend:
│  │
│  ├─ ADD MEDICATION:
│  │  ├─ Medication name, dose, route, frequency
│  │  ├─ Clinical indication
│  │  ├─ Expected benefits
│  │  ├─ Potential side effects/monitoring
│  │  └─ Duration of therapy
│  │
│  ├─ DISCONTINUE MEDICATION:
│  │  ├─ Reason for discontinuation
│  │  ├─ Tapering requirements (if any)
│  │  ├─ Monitoring needed post-discontinuation
│  │  └─ Patient counseling points
│  │
│  ├─ MODIFY DOSE:
│  │  ├─ Current dose
│  │  ├─ Proposed dose
│  │  ├─ Clinical rationale (renal adjustment, toxicity concern, efficacy, etc.)
│  │  ├─ Monitoring parameters
│  │  └─ Timeframe for re-assessment
│  │
│  ├─ MODIFY FREQUENCY:
│  │  ├─ Current frequency
│  │  ├─ Proposed frequency
│  │  ├─ Rationale for change
│  │  └─ Patient counseling on timing
│  │
│  ├─ CHANGE ROUTE:
│  │  ├─ Current route
│  │  ├─ Proposed route
│  │  ├─ Rationale
│  │  └─ Implementation instructions
│  │
│  └─ NO ACTION / ACKNOWLEDGE:
│     ├─ Rationale for accepting discrepancy
│     ├─ Clinical documentation
│     └─ No physician communication needed

FR-INT-001.3 Recommendation Prioritization
├─ System shall allow pharmacist to prioritize recommendations:
│  ├─ URGENT: Requires immediate prescriber communication
│  │  └─ Example: Critical drug-drug interaction, overdose risk
│  │
│  ├─ HIGH: Should be addressed before patient takes medication
│  │  └─ Example: Dose adjustment for renal function, allergy conflict
│  │
│  ├─ ROUTINE: Can be addressed within 24 hours
│  │  └─ Example: Clarification of medication indication
│  │
│  └─ DOCUMENTATION: Information/education only, no prescriber action needed
│
├─ Priority impacts:
│  ├─ Display order on worklist
│  ├─ Notification timing to prescriber
│  └─ Escalation if not addressed within time limits

FR-INT-001.4 Recommendation Documentation
├─ System shall save for each recommendation:
│  ├─ Recommendation ID (auto-generated)
│  ├─ Date/time created
│  ├─ Pharmacist name/ID
│  ├─ Patient ID
│  ├─ Discrepancy addressed
│  ├─ Recommended action
│  ├─ Clinical rationale
│  ├─ Evidence/reference
│  ├─ Priority level
│  ├─ Status (Pending/Communicated/Accepted/Rejected/Not Applicable)
│  ├─ Communication method and time
│  ├─ Response/outcome
│  └─ Date recommendation closed/resolved
```

#### 3.4.2 Prescriber Communication Interface (FR-INT-002)

**Requirement:** System shall facilitate structured communication of recommendations to prescribers.

```
FR-INT-002.1 Recommendation Communication
├─ System shall provide:
│  ├─ Communication template with structured format
│  ├─ Automatic population of key information:
│  │  ├─ Patient name, ID, ward
│  │  ├─ Current medications
│  │  ├─ Recommended change
│  │  ├─ Clinical rationale
│  │  └─ Supporting clinical data
│  │
│  ├─ Flexibility for manual adjustments/additions
│  ├─ Preview of communication before sending
│  ├─ Option to print for paper-based delivery
│  └─ Record of communication (timestamp, method, recipient)
│
├─ Communication Methods:
│  ├─ In-system note (if physician accesses system)
│  ├─ Printed note (for physical handoff)
│  ├─ Phone call (documented with timestamp, message summary)
│  ├─ Email notification (if available) - optional
│  └─ Face-to-face discussion (documented with notes)
│
└─ Communication Record Must Include:
   ├─ Who communicated (pharmacist name)
   ├─ To whom (physician/medical officer name)
   ├─ When (date/time)
   ├─ How (method of communication)
   ├─ What (message summary/full recommendation)
   ├─ Response received (if any)
   └─ Action taken by prescriber

FR-INT-002.2 Recommendation Status Tracking
├─ System shall track status of each recommendation:
│  ├─ PENDING: Created, awaiting communication to prescriber
│  ├─ COMMUNICATED: Sent to prescriber, awaiting response
│  ├─ ACCEPTED: Prescriber approved recommendation, action taken
│  ├─ MODIFIED: Prescriber modified recommendation before action
│  ├─ REJECTED: Prescriber declined recommendation
│  ├─ PENDING PRESCRIBER RESPONSE: Awaiting prescriber decision
│  ├─ NOT APPLICABLE: No action needed/patient situation changed
│  └─ CLOSED: Resolved, no further action
│
├─ Status Transitions:
│  ├─ Automatic status update when communication recorded
│  ├─ Manual status update by pharmacist when prescriber responds
│  ├─ Timestamp for each status change
│  ├─ Automatic escalation if status not updated within 24 hours
│  └─ Escalation notification to pharmacy manager
│
└─ Reports:
   ├─ List of open recommendations awaiting response
   ├─ Recommendations overdue for response
   ├─ Acceptance/rejection rate by recommendation type
   └─ Recommendations by physician (for relationship building)

FR-INT-002.3 Outcome Documentation
├─ When prescriber responds, system shall record:
│  ├─ Response received (date, time, method)
│  ├─ Prescriber decision:
│  │  ├─ ACCEPTED - recommendation implemented
│  │  ├─ ACCEPTED WITH MODIFICATION - modified dose/frequency/etc.
│  │  ├─ REJECTED - reason for rejection (documented by pharmacist)
│  │  ├─ DISCUSSED WITH PATIENT - patient involved in decision
│  │  └─ PENDING - not yet decided
│  │
│  ├─ If modified: specific modifications made
│  ├─ Reason for rejection (if applicable)
│  ├─ Patient/clinical outcome (if known)
│  ├─ Follow-up actions required
│  └─ Date recommendation closed
│
└─ Accepted recommendations:
   ├─ System shall verify updated medication orders entered
   ├─ Updated medication list marked with "Pharmacist Recommended"
   ├─ Clinical note documenting implementation
   └─ Ready for discharge documentation
```

#### 3.4.3 Medication List Finalization (FR-INT-003)

**Requirement:** System shall finalize the reconciled medication list for patient use.

```
FR-INT-003.1 Final Medication List Generation
├─ System shall compile final medication list:
│  ├─ All medications patient is taking at discharge
│  ├─ Including any pharmacist-recommended additions/changes
│  ├─ Excluding discontinued/removed medications
│  ├─ With complete details (dose, frequency, route, timing)
│  ├─ With clinical indications
│  ├─ With any monitoring requirements
│  └─ Marked with reconciliation completion timestamp
│
├─ Final List Contents:
│  ├─ Hospital-initiated medications (to be continued at home)
│  ├─ Home medications to be continued (unchanged)
│  ├─ Home medications with modifications (dose/frequency changed)
│  ├─ Home medications discontinued
│  ├─ New medications started during hospitalization
│  └─ Special instructions/precautions
│
└─ Quality Check:
   ├─ Pharmacist review of final list for accuracy
   ├─ Cross-check against all discrepancies (all addressed?)
   ├─ Verification of drug interactions (resolved?)
   ├─ Check for duplications (removed?)
   └─ Confirmation of patient allergies (still applicable?)

FR-INT-003.2 Patient Medication Card Generation
├─ System shall generate patient-friendly medication card:
│  ├─ Large, easy-to-read format (can be printed)
│  ├─ Patient name and contact information
│  ├─ Each medication listed with:
│  │  ├─ Medication name
│  │  ├─ Strength (e.g., 500mg)
│  │  ├─ Dose (e.g., 1 tablet)
│  │  ├─ When to take (morning, evening, with meals, etc.)
│  │  ├─ What it's for (simple language indication)
│  │  └─ Any special instructions
│  │
│  ├─ Medications to STOP
│  │  ├─ Name
│  │  ├─ Why stopping (if appropriate to share)
│  │  └─ No longer needed / Changed dose / Doctor advised
│  │
│  ├─ Important Safety Information:
│  │  ├─ Known allergies (in red)
│  │  ├─ Medications to avoid
│  │  ├─ Important precautions
│  │  └─ When to call doctor
│  │
│  ├─ Prescriber Information:
│  │  ├─ Doctor name and contact
│  │  ├─ Ward/clinic where discharged from
│  │  └─ Follow-up appointment details (if any)
│  │
│  └─ Format options:
│     ├─ Print on cardstock (wallet-sized)
│     ├─ Print on A4 paper (full page)
│     ├─ Display on phone/tablet
│     └─ SMS option (if mobile number available)

FR-INT-003.3 Discharge Documentation
├─ System shall generate discharge reconciliation report:
│  ├─ Patient demographics and admission details
│  ├─ Primary diagnosis and discharge diagnosis
│  ├─ Medications at admission (from BPMH)
│  ├─ Medications at discharge (final list)
│  ├─ Medication changes (additions, modifications, discontinuations)
│  ├─ Significant discrepancies identified and resolution
│  ├─ Pharmacist recommendations and outcomes
│  ├─ Allergies/ADRs confirmed
│  ├─ Special monitoring requirements
│  ├─ Patient education provided (documented)
│  ├─ Follow-up appointments/actions needed
│  ├─ Pharmacist/date of reconciliation
│  └─ Ready for handoff to primary care
│
└─ Uses:
   ├─ Include in discharge paperwork
   ├─ Send to primary care provider
   ├─ Reference for patient follow-up
   └─ Quality assurance/auditing
```

---

### 3.5 MODULE 5: REPORTING & ANALYTICS

#### 3.5.1 Reconciliation Metrics Dashboard (FR-RA-001)

**Requirement:** System shall provide real-time dashboard for monitoring reconciliation performance.

```
FR-RA-001.1 Key Performance Indicators (KPIs) Display
├─ System shall display:
│  │
│  ├─ SAFETY METRICS:
│  │  ├─ Total patients reconciled (this period)
│  │  ├─ Total unintended discrepancies identified
│  │  ├─ Discrepancies per 100 admissions (trend)
│  │  ├─ Clinically significant UMDs (count & %)
│  │  ├─ UMDs resolved before patient harm (%)
│  │  ├─ Critical drug interactions identified
│  │  └─ Medication allergies conflicts prevented
│  │
│  ├─ PROCESS METRICS:
│  │  ├─ % Admissions with reconciliation completed
│  │  ├─ Mean time to reconciliation completion (hours)
│  │  ├─ Median time to reconciliation completion
│  │  ├─ % Reconciliations completed within 24 hours
│  │  ├─ Pharmacist recommendations per admission
│  │  ├─ % Recommendations accepted by prescriber
│  │  └─ Average time from recommendation to prescriber response
│  │
│  ├─ OPERATIONAL METRICS:
│  │  ├─ Admissions processed per technician (per day)
│  │  ├─ Medications reviewed per pharmacist (per day)
│  │  ├─ Prescriber communication rate (%)
│  │  ├─ System uptime (%)
│  │  └─ User login frequency/engagement
│  │
│  └─ QUALITY METRICS:
│     ├─ Data quality score (completeness, accuracy)
│     ├─ Duplicate entry rate (%)
│     ├─ Allergy/ADR entry completeness (%)
│     └─ Discrepancy classification accuracy (if validated)
│
├─ Display Format:
│  ├─ Large prominent numbers (primary metrics)
│  ├─ Trend indicators (↑ ↓ improvement/decline)
│  ├─ Color-coded status (green=target met, yellow=caution, red=below target)
│  ├─ Comparison to previous period (week, month, quarter)
│  ├─ Benchmarks (target values) displayed
│  └─ Click-through to detail reports

FR-RA-001.2 Dashboard Time Periods
├─ System shall support reporting for:
│  ├─ Real-time (last 24 hours)
│  ├─ Daily
│  ├─ Weekly
│  ├─ Monthly
│  ├─ Quarterly
│  ├─ Year-to-date
│  └─ Custom date ranges
│
├─ Time Period Features:
│  ├─ Default to current week view
│  ├─ Easy navigation (previous/next period buttons)
│  ├─ Date range picker for custom analysis
│  └─ Ability to compare two time periods side-by-side

FR-RA-001.3 Dashboard Filtering & Drill-Down
├─ System shall allow filtering by:
│  ├─ Ward/Department
│  ├─ Shift (if applicable)
│  ├─ Pharmacist (individual performance)
│  ├─ Technician (individual performance)
│  ├─ Patient risk level (high, medium, low)
│  ├─ Discrepancy type
│  ├─ Severity level
│  └─ Admission diagnosis
│
├─ Drill-Down Capability:
│  ├─ Click on metric to see underlying data
│  ├─ List of patients contributing to metric
│  ├─ Patient-level reconciliation details
│  ├─ Individual recommendation details
│  └─ Return to dashboard
```

#### 3.5.2 Discrepancy Analysis Report (FR-RA-002)

**Requirement:** System shall generate detailed analysis of medication discrepancies for quality improvement.

```
FR-RA-002.1 Discrepancy Summary Report
├─ Report shall include:
│  ├─ Total unintended discrepancies identified (period)
│  ├─ Distribution by type:
│  │  ├─ Omissions (count, %)
│  │  ├─ Commissions/New medications (count, %)
│  │  ├─ Dose changes (count, %)
│  │  ├─ Frequency changes (count, %)
│  │  ├─ Duplications (count, %)
│  │  ├─ Route changes (count, %)
│  │  └─ Other (count, %)
│  │
│  ├─ Discrepancies by severity:
│  │  ├─ Critical (count, %)
│  │  ├─ Major (count, %)
│  │  ├─ Minor (count, %)
│  │  └─ Documentation errors (count, %)
│  │
│  ├─ Discrepancies by clinical significance:
│  │  ├─ High significance (count, %)
│  │  ├─ Moderate significance (count, %)
│  │  ├─ Low significance (count, %)
│  │  └─ Unknown (count, %)
│  │
│  ├─ Resolution status:
│  │  ├─ Resolved (count, %)
│  │  ├─ Pending resolution (count, %)
│  │  └─ Unresolved/Accepted (count, %)
│  │
│  ├─ Trends:
│  │  ├─ Comparison to previous period
│  │  ├─ Trend line (improving/stable/declining)
│  │  └─ Seasonal patterns (if data sufficient)
│  │
│  ├─ Top medications involved:
│  │  ├─ Medications most frequently in discrepancies
│  │  ├─ Medication classes with high discrepancy rate
│  │  └─ Drug interactions most frequently identified
│  │
│  └─ Risk factors:
│     ├─ Patient characteristics with highest discrepancy rate
│     ├─ Wards/departments with highest rates
│     ├─ Time patterns (day/night discrepancy rates)
│     └─ Correlation analysis (age, comorbidities, etc.)

FR-RA-002.2 Medication Class Analysis
├─ System shall provide analysis by therapeutic class:
│  ├─ ACE Inhibitors: # discrepancies, types, clinical significance
│  ├─ Antidiabetics: # discrepancies, types, errors
│  ├─ Anticoagulants: # discrepancies, critical incidents
│  ├─ NSAIDs: # discrepancies, renal considerations
│  ├─ Antibiotics: # discrepancies, dosing errors
│  └─ Other classes: similar analysis
│
├─ Class-Specific Insights:
│  ├─ Most common errors in each class
│  ├─ Severity patterns
│  ├─ Preventable vs. system issues
│  └─ Improvement opportunities

FR-RA-002.3 Drug Interaction Summary
├─ Report shall identify:
│  ├─ Most frequently identified interactions
│  ├─ Severity distribution of identified interactions
│  ├─ Interaction types (pharmacokinetic, pharmacodynamic)
│  ├─ Medications most frequently involved
│  ├─ Prescriber responses (accepted/modified/rejected)
│  ├─ Clinical outcomes (if documented)
│  └─ Trend in interactions (improving/increasing)
```

#### 3.5.3 Pharmacist Performance Report (FR-RA-003)

**Requirement:** System shall track and report individual pharmacist performance.

```
FR-RA-003.1 Individual Pharmacist Metrics
├─ For each pharmacist, system shall track:
│  ├─ WORKLOAD:
│  │  ├─ # Reconciliations verified (period)
│  │  ├─ Mean time per reconciliation
│  │  ├─ Workload trend (increasing/stable/decreasing)
│  │  └─ Workload distribution (if multiple pharmacists)
│  │
│  ├─ QUALITY:
│  │  ├─ # Unintended discrepancies identified
│  │  ├─ Discrepancy identification rate (per 100 admissions)
│  │  ├─ Quality trend (improving/stable/declining)
│  │  └─ Comparison to peer average
│  │
│  ├─ RECOMMENDATIONS:
│  │  ├─ # Recommendations formulated (period)
│  │  ├─ Acceptance rate (% by prescribers)
│  │  ├─ Most common recommendation types
│  │  └─ Clinical outcomes tracked
│  │
│  └─ COMMUNICATION:
│     ├─ # Prescriber communications
│     ├─ Mean time to communicate
│     ├─ Communication methods used
│     └─ Response times from prescribers
│
├─ Display Format:
│  ├─ Individual report (one pharmacist)
│  ├─ Peer comparison (anonymized)
│  ├─ Team benchmarking
│  └─ Historical trend analysis

FR-RA-003.2 Technician Performance Report
├─ For each technician, system shall track:
│  ├─ # Interviews conducted (period)
│  ├─ Mean interview duration
│  ├─ # Medications documented per interview (average)
│  ├─ Data quality metrics:
│  │  ├─ Completeness of data entered
│  │  ├─ Accuracy (validated against pharmacist review)
│  │  ├─ Duplicate entry rate
│  │  └─ Error rate
│  │
│  ├─ Source Documentation:
│  │  ├─ # Sources consulted per BPMH (average)
│  │  ├─ Documentation completeness
│  │  └─ Source reliability assessment
│  │
│  └─ Efficiency:
│     ├─ Interviews per shift
│     ├─ Trend over time
│     └─ Comparison to team average

FR-RA-003.3 Peer Benchmarking
├─ System shall provide benchmarking:
│  ├─ Allow comparison of metrics between pharmacists
│  ├─ Highlight outliers (significantly above/below average)
│  ├─ Trend analysis (improving performers)
│  ├─ Quality consistency across team
│  ├─ Identify opportunities for support/training
│  └─ Anonymous/confidential reporting

FR-RA-003.4 User Engagement Metrics
├─ System shall track:
│  ├─ System login frequency (per user)
│  ├─ Features used (which modules accessed)
│  ├─ Average session duration
│  ├─ Inactive users (no logins in X days)
│  ├─ System errors/issues encountered
│  └─ Training completion (if tracked)
```

#### 3.5.4 Standard Reports & Exports (FR-RA-004)

**Requirement:** System shall provide exportable reports for quality improvement and compliance.

```
FR-RA-004.1 Standard Report Library
├─ System shall include pre-built reports:
│  │
│  ├─ DAILY SUMMARY REPORT
│  │  ├─ Admissions reconciled
│  │  ├─ Discrepancies identified
│  │  ├─ Recommendations made
│  │  └─ High-risk alerts
│  │
│  ├─ WEEKLY PERFORMANCE REPORT
│  │  ├─ Metrics summary (safety, process, quality)
│  │  ├─ Discrepancy analysis
│  │  ├─ Pharmacist/technician performance
│  │  └─ Trend analysis
│  │
│  ├─ MONTHLY QUALITY REPORT
│  │  ├─ Comprehensive KPI dashboard
│  │  ├─ Medication class analysis
│  │  ├─ Drug interaction review
│  │  ├─ Performance benchmarking
│  │  └─ Quality improvement recommendations
│  │
│  ├─ PATIENT RECONCILIATION REPORT
│  │  ├─ Patient demographics
│  │  ├─ Admission to discharge medications
│  │  ├─ Discrepancies identified
│  │  ├─ Pharmacist assessments
│  │  ├─ Recommendations and outcomes
│  │  └─ Final discharge list
│  │
│  ├─ DISCREPANCY INCIDENT REPORT
│  │  ├─ Detailed analysis of specific discrepancy
│  │  ├─ Root cause if identified
│  │  ├─ Prevention strategies
│  │  └─ Follow-up actions
│  │
│  └─ ADVERSE EVENT REPORT (if tracked)
│     ├─ Medication-related incidents
│     ├─ Severity classification
│     ├─ Contributing factors
│     └─ Corrective actions

FR-RA-004.2 Export Functionality
├─ Reports shall be exportable in:
│  ├─ PDF format (for printing/distribution)
│  ├─ Excel format (for further analysis)
│  ├─ CSV format (for data import to other systems)
│  └─ HTML format (for viewing in browser)
│
├─ Export Features:
│  ├─ Date range selection
│  ├─ Filter selection (ward, pharmacist, etc.)
│  ├─ Include/exclude specific sections
│  ├─ Logo/watermark (institutional branding)
│  ├─ Auto-generated timestamp
│  └─ Digital signature/authentication (optional)

FR-RA-004.3 Custom Report Builder
├─ Advanced users may create custom reports:
│  ├─ Select metrics to include
│  ├─ Choose time period and filters
│  ├─ Select chart types (bar, line, pie)
│  ├─ Add narrative/commentary
│  ├─ Save for recurring use
│  └─ Share with team/stakeholders

FR-RA-004.4 Data Privacy in Reporting
├─ System shall:
│  ├─ De-identify patient data in aggregate reports
│  ├─ Allow patient-specific reports only to authorized users
│  ├─ Audit all report access
│  ├─ Restrict export to authorized users
│  ├─ Encrypt exported files (optional)
│  └─ Log all report generation
```

---

### 3.6 MODULE 6: USER MANAGEMENT & AUTHENTICATION

#### 3.6.1 User Account Management (FR-UM-001)

**Requirement:** System shall manage user accounts and access control.

```
FR-UM-001.1 User Registration
├─ Administrator may create user accounts with:
│  ├─ Username (unique identifier)
│  ├─ Password (initial, must be changed on first login)
│  ├─ Full name
│  ├─ Email address
│  ├─ Phone number
│  ├─ Department/Ward assignment
│  ├─ Role assignment (Technician/Pharmacist/Physician/Manager/Admin)
│  ├─ Start date
│  └─ Status (Active/Inactive)
│
├─ Validation:
│  ├─ Username must be unique
│  ├─ Username must be 4-20 characters
│  ├─ Email must be valid format
│  └─ Required fields enforced

FR-UM-001.2 Password Management
├─ System shall enforce:
│  ├─ Minimum 8 characters
│  ├─ Must include uppercase, lowercase, number, symbol
│  ├─ Password change required on first login
│  ├─ Password expiration (90 days, configurable)
│  ├─ Password history (last 5 passwords cannot be reused)
│  ├─ Account lockout after 5 failed attempts
│  ├─ Password reset via email link (expires in 1 hour)
│  └─ Session timeout after 30 minutes of inactivity

FR-UM-001.3 User Profile Management
├─ Users may update:
│  ├─ Contact information (email, phone)
│  ├─ Password (self-service)
│  ├─ Profile photo (optional)
│  ├─ Department/ward (if role-appropriate)
│  └─ Notification preferences
│
├─ Users cannot change:
│  ├─ Username
│  ├─ Role/Permissions
│  ├─ Start date
│  └─ User ID

FR-UM-001.4 User Deactivation
├─ Administrator may:
│  ├─ Deactivate inactive user accounts
│  ├─ Archive user accounts (retain data, prevent login)
│  ├─ Delete user accounts (only after retention period)
│  └─ Reassign user's data to another user
│
├─ When user deactivated:
│  ├─ Cannot login to system
│  ├─ Data remains in system (for audit trail)
│  ├─ Historical work attributed to user remains visible
│  ├─ Current active tasks reassigned
│  └─ Supervisor notified
```

#### 3.6.2 Authentication & Authorization (FR-UM-002)

**Requirement:** System shall provide secure authentication and role-based access control.

```
FR-UM-002.1 Login & Session Management
├─ Authentication:
│  ├─ Username and password required
│  ├─ Optional two-factor authentication (2FA via email or SMS)
│  ├─ Session tokens generated upon successful login
│  ├─ Session timeout after 30 minutes of inactivity
│  ├─ Manual logout option available
│  ├─ Concurrent session limit (e.g., one session per user)
│  └─ Login attempt logging (timestamp, IP address, success/failure)
│
├─ Session Security:
│  ├─ HTTPS encryption for all communications
│  ├─ Secure cookies with HttpOnly flag
│  ├─ CSRF protection tokens
│  ├─ Session invalidation on logout
│  └─ Automatic redirect to login on session expiration

FR-UM-002.2 Role-Based Access Control (RBAC)
├─ System shall define roles:
│  │
│  ├─ PHARMACY TECHNICIAN
│  │  ├─ Can: View patient demographics
│  │  ├─ Can: Create/edit medication history
│  │  ├─ Can: Compile BPMH
│  │  ├─ Can: View ward patient list
│  │  ├─ Cannot: Approve/verify reconciliations
│  │  ├─ Cannot: Access reports
│  │  ├─ Cannot: Manage users
│  │  └─ Cannot: Edit patient record (except medications)
│  │
│  ├─ CLINICAL PHARMACIST
│  │  ├─ Can: View all patient data
│  │  ├─ Can: View medication history
│  │  ├─ Can: Perform reconciliation/verification
│  │  ├─ Can: Identify/resolve discrepancies
│  │  ├─ Can: Formulate recommendations
│  │  ├─ Can: Document assessments
│  │  ├─ Can: View metrics/dashboards
│  │  ├─ Can: Generate reports
│  │  ├─ Cannot: Manage users (unless manager role)
│  │  └─ Cannot: Change system configuration
│  │
│  ├─ PHYSICIAN/MEDICAL OFFICER
│  │  ├─ Can: View assigned patient medication lists
│  │  ├─ Can: View pharmacist recommendations
│  │  ├─ Can: Document approval/response
│  │  ├─ Can: View allergy/ADR information
│  │  ├─ Cannot: Modify medication data
│  │  ├─ Cannot: View other physicians' patients (unless same ward)
│  │  └─ Cannot: Access metrics
│  │
│  ├─ NURSE
│  │  ├─ Can: View ward patient medications
│  │  ├─ Can: View final medication list
│  │  ├─ Can: View medication status
│  │  ├─ Cannot: Create/edit medications
│  │  ├─ Cannot: Access recommendations
│  │  └─ Cannot: Access reports
│  │
│  ├─ PHARMACY MANAGER
│  │  ├─ Can: Do all pharmacist functions
│  │  ├─ Can: View all metrics/dashboards
│  │  ├─ Can: Generate all reports
│  │  ├─ Can: View staff performance data
│  │  ├─ Can: Manage system users
│  │  ├─ Can: Configure system settings
│  │  ├─ Can: Export data
│  │  └─ Can: Archive/delete old data
│  │
│  └─ SYSTEM ADMINISTRATOR
│     ├─ Can: Do all functions
│     ├─ Can: User account management
│     ├─ Can: System configuration
│     ├─ Can: Database maintenance
│     ├─ Can: View audit logs
│     ├─ Can: System backup/recovery
│     ├─ Can: Security settings
│     └─ Can: Generate system reports

FR-UM-002.3 Permission Enforcement
├─ System shall enforce at multiple levels:
│  ├─ Page/Module Level:
│  │  ├─ Unauthorized users cannot access pages
│  │  ├─ Menu items hidden based on role
│  │  ├─ Redirected to home page if accessed directly
│  │  └─ Access attempt logged
│  │
│  ├─ Data Level:
│  │  ├─ Pharmacist can see all patient data
│  │  ├─ Technician can only see assigned ward
│  │  ├─ Physician sees only their ward/patients
│  │  ├─ Nurse sees only ward data
│  │  └─ Manager can see all data
│  │
│  └─ Action Level:
│     ├─ Only pharmacists can approve reconciliations
│     ├─ Only managers can create/delete users
│     ├─ Only admins can change system settings
│     ├─ Only authorized users can export data
│     └─ Attempt to perform unauthorized action logged/blocked

FR-UM-002.4 Audit Logging
├─ System shall log:
│  ├─ User login/logout (timestamp, IP address)
│  ├─ Failed login attempts (username, IP, timestamp)
│  ├─ Data access (user, patient, data accessed, timestamp)
│  ├─ Data modifications (user, patient, field changed, before/after value, timestamp)
│  ├─ Report generation (user, report, parameters, timestamp)
│  ├─ Export operations (user, data exported, timestamp)
│  ├─ User account changes (who changed what, when)
│  └─ Unauthorized access attempts (blocked actions, user, timestamp)
│
├─ Audit Trail:
│  ├─ Immutable (cannot be deleted or modified)
│  ├─ Indexed for efficient retrieval
│  ├─ Retention period: 2+ years
│  ├─ Accessible to administrators only
│  └─ Searchable by user, date, action, patient
```

---

## 4. NON-FUNCTIONAL REQUIREMENTS

### 4.1 Performance Requirements (NFR-PERF)

```
NFR-PERF-001: Response Time
├─ Page Load Time: <2 seconds (95th percentile)
├─ Search Results: <2 seconds (95th percentile)
├─ Data Save: <1 second (95th percentile)
├─ Discrepancy Detection: <5 seconds
├─ Report Generation: <10 seconds (small report), <60 seconds (large report)
└─ System shall gracefully degrade with slow connections

NFR-PERF-002: Throughput
├─ Support 10 concurrent users during testing phase
├─ Support 50 concurrent users in pilot phase
├─ Prototype scalable to 100+ concurrent users (architecture allows)
├─ Database queries optimized with appropriate indexing
└─ Caching implemented for frequently accessed data

NFR-PERF-003: Database Performance
├─ Database query response <100ms (95th percentile)
├─ Connection pooling implemented
├─ Indexes on frequently searched fields
├─ Query optimization and monitoring
└─ Regular database maintenance/tuning
```

### 4.2 Availability & Reliability (NFR-AVAIL)

```
NFR-AVAIL-001: System Uptime
├─ Target: 99% uptime during business hours (mon-fri 8am-5pm)
├─ Target: 95% uptime including extended hours
├─ Maintenance windows scheduled outside business hours
├─ Backup systems for critical functionality
└─ Graceful degradation if database unavailable

NFR-AVAIL-002: Backup & Recovery
├─ Database backup: daily (at minimum)
├─ Backup location: offsite or secure secondary location
├─ Recovery time objective (RTO): 4 hours
├─ Recovery point objective (RPO): 24 hours
├─ Regular backup testing (monthly)
├─ Data retention: 2+ years
└─ Disaster recovery plan documented

NFR-AVAIL-003: Offline Functionality
├─ Critical functions available offline (optional for prototype)
├─ Local caching of patient and medication data
├─ Offline forms captured locally
├─ Sync with server when connectivity restored
└─ Conflict resolution for offline updates
```

### 4.3 Security Requirements (NFR-SEC)

```
NFR-SEC-001: Data Encryption
├─ In Transit:
│  ├─ All communications via HTTPS/TLS 1.2+
│  ├─ Certificate from trusted certificate authority
│  ├─ Strong cipher suites (128-bit or higher)
│  └─ HSTS headers enabled
│
├─ At Rest:
│  ├─ Database encryption enabled (if supported)
│  ├─ Sensitive fields encrypted (passwords, SSN, health data)
│  ├─ File encryption if storing sensitive data
│  └─ Encryption key management (secure key storage)

NFR-SEC-002: Authentication & Authorization
├─ Strong password policy enforced
├─ Multi-factor authentication available (email/SMS)
├─ Session timeouts after inactivity
├─ Role-based access control (RBAC)
├─ Principle of least privilege
├─ Regular access reviews
└─ Account lockout after failed attempts

NFR-SEC-003: Data Protection
├─ Data anonymization in test databases
├─ No production data in development environments
├─ Secure handling of export data
├─ Encryption of exported files
├─ Secure deletion of sensitive data (when no longer needed)
└─ GDPR/PDPA compliance (or equivalent for Malaysia)

NFR-SEC-004: Input Validation & Injection Prevention
├─ All user inputs validated
├─ SQL injection prevention (parameterized queries)
├─ Cross-site scripting (XSS) prevention
├─ Cross-site request forgery (CSRF) protection
├─ File upload validation (if applicable)
└─ Regular security scanning/penetration testing

NFR-SEC-005: Audit Trail
├─ All user actions logged
├─ Immutable audit logs
├─ Searchable and retrievable
├─ Retention: 2+ years
├─ Administrator access to logs
└─ Regular audit log review

NFR-SEC-006: Compliance
├─ HIPAA equivalent standards (Health Information Privacy)
├─ Data Protection Impact Assessment (DPIA) if required
├─ Institutional security policies
├─ Regular security training for staff
└─ Incident response procedures documented
```

### 4.4 Usability Requirements (NFR-USA)

```
NFR-USA-001: User Interface
├─ Clean, intuitive design
├─ Consistent across all modules
├─ Mobile-responsive (works on tablets/phones)
├─ Accessibility (WCAG 2.1 AA standard)
├─ Color-blind friendly color schemes
├─ Large readable fonts (minimum 12pt)
└─ Dark mode option (optional)

NFR-USA-002: User Experience
├─ System Usability Scale (SUS) target: ≥68
├─ Comprehensive user documentation
├─ In-app help/tooltips
├─ Error messages clear and actionable
├─ Confirmation for destructive actions
├─ Undo functionality where applicable
└─ Keyboard navigation support

NFR-USA-003: Training & Documentation
├─ User manual provided (2-3 hours training content)
├─ Quick reference guides (job aids)
├─ Video tutorials for key tasks
├─ In-app help system
├─ FAQ section
└─ Support contact information

NFR-USA-004: Reporting & Analytics Usability
├─ Dashboards intuitive and self-explanatory
├─ Report generation wizard for custom reports
├─ Export options clearly labeled
├─ Chart/graph selection based on data type
├─ Print-friendly formatting
└─ Mobile-friendly dashboard views
```

### 4.5 Scalability & Maintainability (NFR-SCALE)

```
NFR-SCALE-001: Scalability
├─ Architecture supports growth:
│  ├─ Horizontal scaling (add servers)
│  ├─ Vertical scaling (increase server resources)
│  ├─ Database sharding capability (future)
│  └─ Load balancing (if needed)
│
├─ Performance monitoring:
│  ├─ System metrics tracked
│  ├─ Bottleneck identification
│  ├─ Performance optimization iterative
│  └─ Capacity planning documentation

NFR-SCALE-002: Maintainability
├─ Code Quality:
│  ├─ Coding standards documented
│  ├─ Code comments for complex logic
│  ├─ Unit test coverage >80%
│  ├─ Automated code quality checks
│  └─ Code review process
│
├─ Documentation:
│  ├─ System architecture documented
│  ├─ Database schema documented
│  ├─ API documentation (if applicable)
│  ├─ Deployment procedures documented
│  └─ Troubleshooting guide
│
├─ Version Control:
│  ├─ Git version control
│  ├─ Branching strategy (dev/test/prod)
│  ├─ Release versioning
│  └─ Change log maintained

NFR-SCALE-003: Upgradability
├─ Database:
│  ├─ Migration scripts for version upgrades
│  ├─ Backward compatibility maintained
│  ├─ Data validation post-migration
│  └─ Rollback procedures available
│
├─ Application:
│  ├─ Feature flags for gradual rollout
│  ├─ Zero-downtime deployment (if possible)
│  ├─ Dependency management
│  └─ Testing on staging environment before production
```

### 4.6 Compatibility Requirements (NFR-COMPAT)

```
NFR-COMPAT-001: Browser Compatibility
├─ Chrome: Latest 2 versions
├─ Firefox: Latest 2 versions
├─ Safari: Latest 2 versions
├─ Edge: Latest 2 versions
├─ Mobile browsers (iOS Safari, Chrome Mobile)
└─ Graceful degradation for older browsers

NFR-COMPAT-002: Operating System
├─ Windows: Server 2019+, Win 10+
├─ macOS: 10.15+
├─ Linux: Ubuntu 20.04+ or equivalent
└─ Mobile: iOS 13+, Android 10+

NFR-COMPAT-003: Database Compatibility
├─ PostgreSQL 12+ (recommended)
├─ MySQL 8.0+ (alternative)
├─ Compatibility layer abstracts database differences
└─ Regular testing on both platforms
```

---

## 5. DATA MODEL & DATABASE SCHEMA

### 5.1 Entity-Relationship Diagram (ERD)

```
┌─────────────────┐
│     PATIENT     │
├─────────────────┤
│ PK patient_id   │──┐
│    name         │  │
│    dob          │  │
│    gender       │  │
│    contact      │  │ 1..N
│    admission_dt │  │
│    ward_id      │  │
│    diagnosis    │  │
│    allergies    │  │
│    renal_func   │  │
│    risk_level   │  │
└─────────────────┘  │
                     │
     ┌───────────────┼───────────────────┐
     │               │                   │
┌────▼────────────┐ │   ┌───────────────▼──────────┐
│ RECONCILIATION  │ │   │ MEDICATION_HISTORY      │
├─────────────────┤ │   ├────────────────────────┤
│ PK recon_id     │ │   │ PK med_hist_id         │
│ FK patient_id   │──   │ FK patient_id          │
│    recon_type   │     │ FK recon_id            │
│    start_time   │     │    med_name            │
│    end_time     │     │    dose                │
│    technician   │     │    route               │
│    pharmacist   │     │    frequency           │
│    status       │     │    indication          │
│    notes        │     │    start_date          │
└────────────────┘     │    prescriber          │
                       │    source              │
                       │    is_taking           │
                       │    adherence           │
                       │    created_dt          │
                       └────────────────────────┘

            ┌──────────────────────────┐
            │   MEDICATION_CURRENT     │
            ├──────────────────────────┤
            │ PK med_curr_id           │
            │ FK recon_id              │
            │    med_name              │
            │    dose                  │
            │    route                 │
            │    frequency             │
            │    indication            │
            │    ordered_by            │
            │    order_date            │
            └──────────────────────────┘

┌─────────────────────────┐
│     DISCREPANCY         │
├─────────────────────────┤
│ PK discrepancy_id       │
│ FK recon_id             │
│ FK med_hist_id (opt)    │
│ FK med_curr_id (opt)    │
│    type                 │
│    severity             │
│    clinical_sig         │
│    status               │
│    notes                │
│    created_dt           │
│    resolved_dt          │
└─────────────────────────┘

┌──────────────────────────┐
│  RECOMMENDATION          │
├──────────────────────────┤
│ PK rec_id                │
│ FK recon_id              │
│ FK discrepancy_id (opt)  │
│    action                │
│    detail                │
│    rationale             │
│    priority              │
│    status                │
│    communicated_date     │
│    response              │
│    pharmacist_id         │
│    physician_id (opt)    │
│    created_dt            │
│    resolved_dt           │
└──────────────────────────┘

┌──────────────────────┐
│       USER           │
├──────────────────────┤
│ PK user_id           │
│    username          │
│    password_hash     │
│    full_name         │
│    email             │
│    phone             │
│    role              │
│    department        │
│    is_active         │
│    created_dt        │
│    last_login        │
│    pwd_changed_dt    │
└──────────────────────┘

┌─────────────────────┐
│    AUDIT_LOG        │
├─────────────────────┤
│ PK log_id           │
│ FK user_id          │
│    action           │
│    table_name       │
│    record_id        │
│    old_value        │
│    new_value        │
│    timestamp        │
│    ip_address       │
│    status           │
└─────────────────────┘
```

### 5.2 Database Schema Details

**PATIENT Table:**
```sql
CREATE TABLE patients (
    patient_id VARCHAR(20) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other'),
    contact_primary VARCHAR(20),
    contact_secondary VARCHAR(20),
    email VARCHAR(100),
    address_street VARCHAR(255),
    address_city VARCHAR(100),
    address_postcode VARCHAR(10),
    address_state VARCHAR(50),
    admission_date DATETIME NOT NULL,
    discharge_date DATETIME,
    ward_id VARCHAR(50),
    primary_diagnosis VARCHAR(255),
    allergies TEXT,
    known_adrs TEXT,
    renal_function ENUM('Normal', 'Mild_Impairment', 'Moderate_Impairment', 'Severe_Impairment'),
    egfr DECIMAL(5,2),
    hepatic_function ENUM('Normal', 'Mild', 'Moderate', 'Severe'),
    pregnancy_status ENUM('Unknown', 'Not_Pregnant', 'Pregnant'),
    is_high_risk BOOLEAN,
    notes TEXT,
    created_by VARCHAR(50),
    created_date DATETIME,
    updated_by VARCHAR(50),
    updated_date DATETIME,
    status ENUM('Active', 'Discharged', 'Archived')
);
```

**MEDICATION_HISTORY Table:**
```sql
CREATE TABLE medication_history (
    med_hist_id VARCHAR(20) PRIMARY KEY,
    patient_id VARCHAR(20) NOT NULL,
    reconciliation_id VARCHAR(20),
    medication_name VARCHAR(255) NOT NULL,
    strength VARCHAR(100),
    dose_amount DECIMAL(10,2),
    dose_unit VARCHAR(50),
    route ENUM('PO', 'IV', 'IM', 'SC', 'Topical', 'Inhaled', 'Other'),
    frequency VARCHAR(100),
    timing VARCHAR(255),
    indication VARCHAR(255),
    start_date DATE,
    stop_date DATE,
    prescriber_name VARCHAR(255),
    prescriber_facility VARCHAR(255),
    is_patient_taking ENUM('Yes', 'No', 'Not_Sure'),
    adherence_level ENUM('Full', 'Partial', 'None', 'Unknown'),
    non_adherence_reason VARCHAR(255),
    source_type ENUM('Patient_Report', 'Family', 'Med_Bottle', 'Previous_Record', 'Pharmacy', 'Other'),
    source_details VARCHAR(255),
    reliability_rating ENUM('Definite', 'Probable', 'Possible'),
    data_quality_score INT,
    created_by VARCHAR(50),
    created_date DATETIME,
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
);
```

**RECONCILIATION Table:**
```sql
CREATE TABLE reconciliations (
    reconciliation_id VARCHAR(20) PRIMARY KEY,
    patient_id VARCHAR(20) NOT NULL,
    reconciliation_type ENUM('Admission', 'Transfer', 'Discharge'),
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME,
    technician_id VARCHAR(50),
    pharmacist_id VARCHAR(50),
    status ENUM('Draft', 'In_Progress', 'Completed', 'Closed'),
    bpmh_finalized BOOLEAN,
    total_discrepancies INT DEFAULT 0,
    critical_discrepancies INT DEFAULT 0,
    unresolved_discrepancies INT DEFAULT 0,
    clinical_notes TEXT,
    created_date DATETIME,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (technician_id) REFERENCES users(user_id),
    FOREIGN KEY (pharmacist_id) REFERENCES users(user_id)
);
```

**DISCREPANCY Table:**
```sql
CREATE TABLE discrepancies (
    discrepancy_id VARCHAR(20) PRIMARY KEY,
    reconciliation_id VARCHAR(20) NOT NULL,
    med_hist_id VARCHAR(20),
    med_current_id VARCHAR(20),
    discrepancy_type ENUM('Omission', 'Commission', 'Dose_Change', 'Frequency_Change', 
                          'Route_Change', 'Duplication', 'Therapeutic_Duplication', 'Other'),
    severity ENUM('Critical', 'Major', 'Minor', 'Documentation'),
    clinical_significance ENUM('High', 'Moderate', 'Low', 'Unknown'),
    status ENUM('Identified', 'Under_Review', 'Resolved', 'Pending_Prescriber', 'Closed'),
    description TEXT,
    pharmacist_assessment VARCHAR(255),
    created_date DATETIME,
    resolved_date DATETIME,
    resolved_by VARCHAR(50),
    FOREIGN KEY (reconciliation_id) REFERENCES reconciliations(reconciliation_id),
    FOREIGN KEY (med_hist_id) REFERENCES medication_history(med_hist_id)
);
```

**RECOMMENDATION Table:**
```sql
CREATE TABLE recommendations (
    recommendation_id VARCHAR(20) PRIMARY KEY,
    reconciliation_id VARCHAR(20) NOT NULL,
    discrepancy_id VARCHAR(20),
    action_type ENUM('Add', 'Delete', 'Modify_Dose', 'Modify_Frequency', 'Change_Route', 'No_Action'),
    medication_name VARCHAR(255),
    details TEXT,
    clinical_rationale TEXT,
    evidence_reference VARCHAR(255),
    alternative_drugs VARCHAR(255),
    priority ENUM('Urgent', 'High', 'Routine', 'Documentation'),
    status ENUM('Pending', 'Communicated', 'Accepted', 'Modified', 'Rejected', 'Not_Applicable', 'Closed'),
    pharmacist_id VARCHAR(50) NOT NULL,
    created_date DATETIME,
    communicated_date DATETIME,
    communicated_to VARCHAR(50),
    communication_method VARCHAR(100),
    prescriber_response ENUM('Accepted', 'Accepted_Modified', 'Rejected', 'Pending', 'Discussed_With_Patient'),
    response_notes TEXT,
    response_date DATETIME,
    outcome VARCHAR(255),
    resolved_date DATETIME,
    FOREIGN KEY (reconciliation_id) REFERENCES reconciliations(reconciliation_id),
    FOREIGN KEY (discrepancy_id) REFERENCES discrepancies(discrepancy_id),
    FOREIGN KEY (pharmacist_id) REFERENCES users(user_id)
);
```

**USER Table:**
```sql
CREATE TABLE users (
    user_id VARCHAR(50) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('Technician', 'Pharmacist', 'Physician', 'Nurse', 'Manager', 'Admin'),
    department VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_date DATETIME,
    updated_date DATETIME,
    last_login DATETIME,
    password_changed_date DATETIME,
    pwd_expiry_date DATETIME,
    failed_login_attempts INT DEFAULT 0,
    account_locked BOOLEAN DEFAULT FALSE,
    locked_until DATETIME
);
```

**AUDIT_LOG Table:**
```sql
CREATE TABLE audit_logs (
    log_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id VARCHAR(20),
    old_value TEXT,
    new_value TEXT,
    action_timestamp DATETIME NOT NULL,
    ip_address VARCHAR(45),
    action_status ENUM('Success', 'Failure'),
    error_message VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX (user_id, action_timestamp),
    INDEX (table_name, record_id)
);
```

---

## 6. USE CASES

### 6.1 Primary Use Cases

#### UC-01: Pharmacist Technician Collects Medication History

```
Actor: Pharmacy Technician
Preconditions: Patient admitted, record created in system
Normal Flow:
1. Technician logs into system
2. Selects "New Medication Reconciliation"
3. Searches for patient by ID or name
4. System displays patient demographics and allergies
5. Technician navigates to "Medication Interview" section
6. Enters each medication patient is taking:
   - Medication name
   - Dose and frequency
   - Route and timing
   - Indication
   - When started
7. Documents over-the-counter and supplements
8. Documents source of information
9. System highlights discrepancies between sources (if multiple)
10. Technician marks interview complete
11. System compiles BPMH
12. Technician reviews BPMH before submission
13. Submits for pharmacist verification
14. System sends notification to pharmacist
Postconditions: BPMH created and ready for pharmacist verification
Alternate Flows:
  - Patient unable to recall medications: Technician notes and marks for pharmacist follow-up
  - Multiple information sources conflict: System displays all versions for pharmacist resolution
Exceptions:
  - Patient information incomplete: System shows warning, allows draft save
  - System crashes: Technician work saved to local storage, syncs when reconnected
```

#### UC-02: Pharmacist Verifies and Identifies Discrepancies

```
Actor: Clinical Pharmacist
Preconditions: BPMH compiled, current medication list available
Normal Flow:
1. Pharmacist logs into system
2. Accesses "Medication Verification" task queue
3. Selects HIGH-RISK patient reconciliation
4. Reviews patient demographics, allergies, clinical context
5. Reviews BPMH compiled by technician
6. Views medication sources and notes
7. Enters/accesses current medication list (admission orders)
8. System performs automatic discrepancy detection:
   - Flags medications in BPMH but not in current list (omissions)
   - Flags new medications not in BPMH
   - Checks for dose/frequency changes
   - Identifies drug duplications and interactions
9. System provides clinical decision support:
   - Drug-drug interaction alerts
   - Dosage appropriateness checks (renal/hepatic function)
   - Allergy conflicts highlighted
10. Pharmacist reviews each discrepancy:
    - Assesses if intentional or unintended
    - Determines clinical significance
    - Documents assessment
11. For critical/major unintended discrepancies, formulates recommendations:
    - ADD medication (if omitted)
    - DELETE medication (if duplicate/inappropriate)
    - MODIFY dose (if renal adjustment needed, for example)
12. Documents clinical rationale for recommendations
13. Submits verification and recommendations
14. System notifies physician of recommendations
Postconditions: Discrepancies identified, pharmacist assessment documented, ready for prescriber communication
Alternate Flows:
  - Pharmacist unable to clarify discrepancy: Marks for follow-up conversation with physician
  - Multiple sources conflict significantly: Documents conflict, follows up with patient
Exceptions:
  - Critical drug interaction identified: System alerts prominently, escalation to manager
```

#### UC-03: Pharmacist Communicates Recommendation to Physician

```
Actor: Clinical Pharmacist, Physician/Medical Officer
Preconditions: Pharmacist has formulated recommendations
Normal Flow:
1. Pharmacist accesses "Recommendations" list
2. Selects recommendation to communicate
3. Reviews recommendation details:
   - Medication involved
   - Proposed action
   - Clinical rationale
   - Evidence/reference
4. System generates recommendation communication:
   - Patient summary
   - Current medications
   - Proposed change
   - Clinical rationale
   - Drug interaction data (if relevant)
5. Pharmacist reviews generated communication
6. Selects communication method:
   - Print and hand to physician (paper note)
   - In-system message (if physician uses system)
   - Phone call (document conversation)
7. Records communication:
   - Date/time
   - Method
   - Recipient
8. Documents prescriber response:
   - Accepted as recommended
   - Accepted with modifications
   - Rejected (with reason)
   - Pending (awaiting response)
9. If accepted: Recommendation marked CLOSED
10. If modified: Records modifications, new medication orders reviewed
11. If rejected: Documents reason, follows up if needed
Postconditions: Physician response documented, medication orders updated if approved
Alternate Flows:
  - Physician not available: Pharmacist documents attempted communication, tries again later
  - Physician modifies recommendation: System updates medication list with modifications
Exceptions:
  - Physician rejects critical recommendation: Escalate to pharmacy manager for follow-up
```

#### UC-04: Patient Receives Discharge Medication Instructions

```
Actor: Clinical Pharmacist, Patient
Preconditions: Final reconciliation complete, discharge date
Normal Flow:
1. Patient approaching discharge identified
2. Pharmacist prepares discharge reconciliation:
   - Reviews admission medications (BPMH)
   - Reviews discharge medications (final list)
   - Identifies changes from admission
3. System generates comparison report
4. Pharmacist counsels patient:
   - Explains each medication
   - Discusses changes from before hospitalization
   - Reviews dosages and timing
   - Discusses side effects and monitoring
   - Addresses adherence barriers
   - Documents patient understanding
5. Patient receives:
   - Discharge medication card (wallet-sized or A4)
   - Printed medication list
   - Doctor's contact information
   - Follow-up appointment details
6. Pharmacist documents counseling in patient record
7. Notes any patient concerns or special needs
8. Marks reconciliation CLOSED at discharge
Postconditions: Patient educated, discharge documentation complete, final medication list available
Alternate Flows:
  - Patient requests clarification: Pharmacist re-educates, documents discussion
  - Patient expresses concern about medication: Escalate to physician if needed
Exceptions:
  - Patient cognitive impairment: Include caregiver in counseling, provide written materials
  - Language barrier: Use interpretation services, provide translated materials if available
```

#### UC-05: Generate Quality Metrics Report

```
Actor: Pharmacy Manager, Quality Improvement Team
Preconditions: Reconciliation data available for reporting period
Normal Flow:
1. Manager logs into system
2. Accesses "Reporting" module
3. Selects "Monthly Quality Report"
4. System displays interactive dashboard:
   - KPI summary (admissions, discrepancies, safety outcomes)
   - Trend charts (month-over-month, quarter-over-quarter)
   - Pharmacy team performance
   - Medication class analysis
   - Drug interaction frequencies
5. Manager filters dashboard:
   - By ward/department
   - By pharmacist
   - By time period
6. System displays:
   - Details behind each metric
   - Patient-level data
   - Trend analysis
   - Benchmark comparison
7. Manager exports report as PDF/Excel
8. Prepares analysis document:
   - Key findings
   - Areas of improvement
   - Performance highlights
   - Quality improvement recommendations
9. Presents to pharmacy team/hospital leadership
Postconditions: Monthly report generated, quality metrics analyzed, improvement opportunities identified
Alternate Flows:
  - Manager customizes report: Uses custom report builder to select specific metrics
  - Unusual pattern identified: Manager drills into data for root cause analysis
Exceptions:
  - Data incomplete for period: System notes data gaps, flags for follow-up data entry
```

---

## 7. USER ROLES & PERMISSIONS

### Permission Matrix

| Feature | Tech | Pharm | Phys | Nurse | Mgr | Admin |
|---------|------|-------|------|-------|-----|-------|
| **Patient Management** |
| View patient record | W | R | R | R | R | R |
| Create patient record | — | R | — | — | R | R |
| Edit patient record | — | W | — | — | W | W |
| **Medication History** |
| Create med history | W | R | R | — | R | R |
| Edit med history | W | W | — | — | W | W |
| View med history | W | R | R | R | R | R |
| **Reconciliation** |
| Create reconciliation | W | R | — | — | R | R |
| Verify reconciliation | — | W | — | — | W | W |
| View reconciliation | W | R | R | R | R | R |
| Edit reconciliation | — | W | — | — | W | W |
| **Discrepancies** |
| View discrepancies | W | R | — | R | R | R |
| Assess discrepancies | — | W | — | — | W | W |
| Resolve discrepancies | — | W | R | — | W | W |
| **Recommendations** |
| Create recommendation | — | W | — | — | W | W |
| View recommendation | W | R | R | R | R | R |
| Approve recommendation | — | — | W | — | W | W |
| **Reports** |
| View dashboard | — | R | — | — | W | W |
| Generate reports | — | R | — | — | W | W |
| Export data | — | — | — | — | W | W |
| **User Management** |
| Create user | — | — | — | — | — | W |
| Edit user | — | — | — | — | — | W |
| Delete user | — | — | — | — | — | W |
| View audit logs | — | — | — | — | — | W |
| **System Configuration** |
| Change settings | — | — | — | — | — | W |
| Configure alerts | — | — | — | — | — | W |

Legend: W = Write, R = Read, — = No Access

---

## 8. SYSTEM ARCHITECTURE

### 8.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         PRESENTATION LAYER                      │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Web Application (Laravel Blade Templates)               │   │
│  │  ├─ Patient Management UI (Bootstrap 5 Responsive)       │   │
│  │  ├─ Medication History Interface                         │   │
│  │  ├─ Reconciliation Verification Dashboard               │   │
│  │  ├─ Recommendation Management Interface                 │   │
│  │  ├─ Reports & Analytics Dashboard (Chart.js)            │   │
│  │  ├─ User Management Portal                              │   │
│  │  └─ Mobile-Responsive Design (Bootstrap Grid)           │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              ↕ HTTP (Request/Response)
┌─────────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER (Backend)                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Laravel Framework 10.x/11.x                             │   │
│  │  ├─ Routing (Web routes & API routes)                    │   │
│  │  ├─ Controllers (Business logic for each module)         │   │
│  │  ├─ Middleware (Authentication, Authorization, CORS)    │   │
│  │  └─ Service Classes (Reusable business logic)           │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Authentication & Authorization                          │   │
│  │  ├─ Laravel Sanctum (Session-based auth for web)         │   │
│  │  ├─ Role-Based Access Control (RBAC via Policies)       │   │
│  │  ├─ Password hashing (bcrypt)                            │   │
│  │  └─ Session management (Cookies)                         │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Patient Controller & Service                            │   │
│  │  ├─ Patient CRUD operations                              │   │
│  │  ├─ Risk stratification logic                            │   │
│  │  ├─ Data validation (Laravel Validation Rules)           │   │
│  │  └─ Search & filtering (Query scopes)                    │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Medication History Controller & Service                 │   │
│  │  ├─ CRUD for medication records                          │   │
│  │  ├─ BPMH compilation logic                               │   │
│  │  ├─ Source documentation management                      │   │
│  │  ├─ Data quality checks                                  │   │
│  │  └─ Validation rules                                     │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Reconciliation Engine Controller & Service              │   │
│  │  ├─ Discrepancy detection algorithm                      │   │
│  │  ├─ Medication list comparison logic                     │   │
│  │  ├─ Severity classification                              │   │
│  │  ├─ Status management                                    │   │
│  │  └─ Validation workflows                                 │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Clinical Decision Support Service                       │   │
│  │  ├─ Drug-drug interaction checking                       │   │
│  │  ├─ Drug-disease contraindication assessment             │   │
│  │  ├─ Dosage appropriateness validation                    │   │
│  │  ├─ Allergy cross-reactivity checking                    │   │
│  │  └─ Therapeutic duplication detection                    │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Recommendation Controller & Service                     │   │
│  │  ├─ Recommendation creation & formulation                │   │
│  │  ├─ Prescriber communication management                  │   │
│  │  ├─ Status tracking                                      │   │
│  │  ├─ Outcome documentation                                │   │
│  │  └─ Follow-up escalation logic                           │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Reporting & Analytics Controller & Service              │   │
│  │  ├─ KPI calculation engine                               │   │
│  │  ├─ Metric aggregation                                   │   │
│  │  ├─ Report generation (PDF/Excel export)                 │   │
│  │  ├─ Data export functionality                            │   │
│  │  ├─ Chart data aggregation (JSON for Chart.js)           │   │
│  │  └─ Trend analysis                                       │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Notification Service                                    │   │
│  │  ├─ Email notifications (Laravel Mail)                   │   │
│  │  ├─ In-app notifications (Flash messages/DB)             │   │
│  │  ├─ SMS alerts (optional third-party)                    │   │
│  │  └─ Escalation alerts                                    │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Audit & Logging (Laravel Logging)                       │   │
│  │  ├─ Action logging                                       │   │
│  │  ├─ Data modification tracking (Audit Observer)          │   │
│  │  ├─ Access logging (Middleware)                          │   │
│  │  └─ Compliance reporting                                 │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              ↕ Eloquent ORM/SQL
┌─────────────────────────────────────────────────────────────────┐
│                      DATABASE LAYER                             │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  MySQL 8.0+ (Local Server)                               │   │
│  │  ├─ Patient records                                      │   │
│  │  ├─ Medication data                                      │   │
│  │  ├─ Reconciliation records                               │   │
│  │  ├─ Discrepancy tracking                                 │   │
│  │  ├─ User/Role data                                       │   │
│  │  ├─ Audit logs                                           │   │
│  │  └─ System configuration                                 │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  File Storage                                            │   │
│  │  ├─ Reports (PDF/Excel export storage)                   │   │
│  │  ├─ Logs (Application logs)                              │   │
│  │  ├─ Backups (MySQL database backups)                     │   │
│  │  └─ Temporary files (Cache)                              │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 8.2 Technology Stack Recommendation

**Frontend:**
- Framework: Laravel Blade Templates (Server-side rendering)
- UI Components: Bootstrap 5 or Tailwind CSS
- Charts/Visualizations: Chart.js, ApexCharts
- Form Handling: Laravel Collective or Blade forms
- JavaScript: Vanilla JS or Alpine.js (lightweight)
- Responsive Design: Mobile-first Bootstrap Grid

**Backend:**
- Runtime: PHP 8.1+ (Laravel Framework 10.x or 11.x)
- Framework: Laravel (Full-stack PHP framework)
- API: RESTful API (Laravel API routes)
- Authentication: Laravel Sanctum (for API tokens) or Session-based auth
- ORM: Eloquent (Laravel's built-in ORM)
- Package Manager: Composer

**Database:**
- Primary: MySQL 8.0+ (recommended for local server)
- Alternative: MariaDB 10.6+
- Local Development: XAMPP/WAMP/LAMP stack
- Optional Cache: Redis (for session storage, optional)

**Local Server Deployment:**
- Server OS: Windows Server, Linux (Ubuntu), or MacOS
- Web Server: Apache 2.4+ or Nginx 1.20+
- PHP: PHP 8.1+ with required extensions (mysqli, PDO, OpenSSL, etc.)
- Development Environment: XAMPP 8.0+, WAMP, or LEMP stack
- Version Control: Git (GitHub/GitLab or local Git server)
- Local Access: Via localhost:8000 or custom domain (local DNS entry)

**Development & Testing:**
- Unit Testing: PHPUnit (Laravel's testing framework)
- Feature Testing: Laravel Testing with Pest or PHPUnit
- Browser Testing: Laravel Dusk (browser automation)
- Database Testing: SQLite in-memory for tests
- Code Quality: PHPStan, PHP_CodeSniffer, Laravel Pint

**Local Server Infrastructure:**
- Single Server Setup: All components (web server, PHP, MySQL) on one machine
- Server Resources: Minimum 4GB RAM, 50GB storage for prototype phase
- Backup: Local backup scripts (MySQL dumps, file backups)
- Monitoring: Native server monitoring or simple dashboard
- Security: SSL certificates (self-signed for local development, purchased for production)

---

## 9. INTERFACE REQUIREMENTS

### 9.1 Key User Interface Screens

#### Screen 1: Dashboard (Main Landing Page)
```
┌─────────────────────────────────────────────────────────────┐
│  MEDICATION RECONCILIATION SYSTEM - SASMEC @IIUM            │
├─────────────────────────────────────────────────────────────┤
│  [Profile] [Settings] [Logout]                      [Logout]│
├─────────────────────────────────────────────────────────────┤
│  Welcome, John (Pharmacist)                                 │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ TODAY'S SUMMARY                                     │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │                                                     │   │
│  │  Admissions Reconciled: 12    ↑ 20%               │   │
│  │  Pending Verifications: 5     ↓ Drug Interactions │   │
│  │  Recommendations Awaiting Response: 3             │   │
│  │  Critical Alerts: 1          ⚠️ View Details     │   │
│  │                                                     │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌──────────────────┐  ┌──────────────────┐               │
│  │ QUICK ACTIONS    │  │ RECENT ACTIVITY  │               │
│  ├──────────────────┤  ├──────────────────┤               │
│  │ [+ New Patient]  │  │ • 10:30: Patient │               │
│  │ [+ New Interview]│  │   123 discharged │               │
│  │ [View Worklist]  │  │ • 10:15: Drug    │               │
│  │ [Reports]        │  │   interaction    │               │
│  │ [Settings]       │  │   flagged        │               │
│  │                  │  │ • 9:45: Recom    │               │
│  │                  │  │   accepted       │               │
│  └──────────────────┘  └──────────────────┘               │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ MONTHLY METRICS (June 2026)                         │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │ Admissions: 245  |  UMDs: 82  |  Safety: 98%      │   │
│  │                                                     │   │
│  │ [Line Chart: Trend]                                 │   │
│  │                                                     │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### Screen 2: Patient Search & Selection
```
┌─────────────────────────────────────────────────────────────┐
│ FIND PATIENT                                                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Search: [___________]  [🔍 Search]  [+ New Patient]       │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ SEARCH RESULTS (5 matches)                          │   │
│  ├────┬────────────┬──────┬────────┬────────────────┤   │
│  │ID  │Name        │DOB   │Adm Dt  │Ward            │   │
│  ├────┼────────────┼──────┼────────┼────────────────┤   │
│  │001 │Ahmad Bin   │5/14  │Today   │Medical Ward    │   │
│  │    │Ali         │1960  │08:00   │🚩 HIGH RISK    │   │
│  ├────┼────────────┼──────┼────────┼────────────────┤   │
│  │002 │Siti Nur    │3/22  │Yd      │Surgical Ward   │   │
│  │    │Azizah      │1975  │14:30   │✓ Ready         │   │
│  ├────┼────────────┼──────┼────────┼────────────────┤   │
│  │... │            │      │        │                │   │
│  └────┴────────────┴──────┴────────┴────────────────┘   │
│                                                             │
│  [< Previous] [1] [2] [3] [Next >]                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### Screen 3: Medication History Interview
```
┌──────────────────────────────────────────────────────────────┐
│ MEDICATION HISTORY INTERVIEW                                │
├──────────────────────────────────────────────────────────────┤
│ Patient: Ahmad Bin Ali (ID: 001)  │  🚩 HIGH RISK           │
│ Ward: Medical Ward                │  Age: 64, Renal impair. │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│ ⚠️ ALLERGIES: Penicillin (Anaphylaxis), NSAIDs (Rash)      │
│                                                              │
│ Interview Progress: 1 of 3 medications completed            │
│                                                              │
│ Add Medication:                                              │
│ ┌──────────────────────────────────────────────────────┐    │
│ │ Medication Name: [Amlodipine      ▼] (autocomplete) │    │
│ │ Strength: [5mg              ]                         │    │
│ │ Dose: [1    ] Unit: [tablet ▼]                      │    │
│ │ Route: [PO  ▼]                                        │    │
│ │ Frequency: [Once Daily ▼]                           │    │
│ │ Timing: [Morning with breakfast]                     │    │
│ │ Indication: [Blood Pressure      ]                   │    │
│ │ Start Date: [01/01/2020    ]                         │    │
│ │ Prescriber: [Dr. Ramli, Clinic] (optional)          │    │
│ │ Source: [✓] Patient ☐ Bottle ☐ Record              │    │
│ │ Is Patient Still Taking? [✓] Yes ☐ No ☐ Not Sure   │    │
│ │                                                       │    │
│ │ [+ Add] [Save Draft] [Clear]                         │    │
│ └──────────────────────────────────────────────────────┘    │
│                                                              │
│ Current Medications Listed:                                  │
│ ┌───────────────────────────────────────────────────────┐   │
│ │ 1. Amlodipine 5mg, PO, Once Daily (BP) - Patient    │   │
│ │ 2. Metformin 500mg, PO, BID (Diabetes) - Bottle     │   │
│ └───────────────────────────────────────────────────────┘   │
│                                                              │
│ [Prev: OTC Meds] [Next: Sources] [Submit for Verification]  │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

#### Screen 4: Pharmacist Verification & Reconciliation
```
┌────────────────────────────────────────────────────────────┐
│ MEDICATION RECONCILIATION VERIFICATION                    │
├────────────────────────────────────────────────────────────┤
│ Patient: Ahmad Bin Ali (ID: 001)                           │
│ Risk Level: 🚩 HIGH  |  BPMH Compiled: ✓  |  Status: In Progress
├────────────────────────────────────────────────────────────┤
│                                                            │
│ ALLERGIES ⚠️: Penicillin (Anaphylaxis), NSAIDs (Rash)     │
│ RENAL FUNCTION: Mild impairment (eGFR 55)                 │
│                                                            │
│ ┌────────────────────────────────────────────────────┐    │
│ │ BPMH (From Patient Interview) │ Current Orders    │    │
│ ├────────────────────────────────┼───────────────────┤    │
│ │1. Amlodipine 5mg PO OD        │1. Amlodipine 5mg│    │
│ │2. Metformin 500mg PO BID      │2. Metformin 500 │    │
│ │3. Lisinopril 10mg PO OD       │⛔ MISSING        │    │
│ │4. Aspirin 75mg PO OD          │3. Aspirin 100mg │    │
│ │ (Home: 75mg)                  │(Dose Changed!)  │    │
│ │                               │4. ❓ Clopidogrel│    │
│ │                               │    (NEW)         │    │
│ └────────────────────────────────┴───────────────────┘    │
│                                                            │
│ IDENTIFIED DISCREPANCIES:                                  │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ 1. ⛔ CRITICAL: Lisinopril OMITTED (ACE inhibitor)   │   │
│ │    [Patient was taking, now not prescribed]         │   │
│ │    [For: Hypertension/CKD - renal protective]       │   │
│ │    □ Unintended □ Intentional [Unintended]          │   │
│ │    Clinical Note: __________________________         │   │
│ │    Recommendation: [+ ADD BACK]                      │   │
│ ├──────────────────────────────────────────────────────┤   │
│ │ 2. 🟠 MAJOR: Aspirin dose CHANGED (75mg→100mg)      │   │
│ │    [Patient dose change]                             │   │
│ │    □ Intentional [Intentional] □ Unintended          │   │
│ │    Reason: Cardiac event risk increase?              │   │
│ │    Clinical Note: __________________________         │   │
│ ├──────────────────────────────────────────────────────┤   │
│ │ 3. 🟡 MODERATE: Clopidogrel NEW (not in BPMH)       │   │
│ │    [New medication started in hospital]              │   │
│ │    □ Appropriate [Appropriate] □ Duplication        │   │
│ │    Clinical Note: Post-PCI, approved                 │   │
│ └──────────────────────────────────────────────────────┘   │
│                                                            │
│ CLINICAL DECISION SUPPORT:                                 │
│ ⚠️ Drug Interaction: Aspirin + Clopidogrel = MODERATE     │   
│    [Increased bleeding risk - requires monitoring]        │
│ ⚠️ Dosage Alert: Metformin in renal impairment           │   
│    [eGFR 55 - dose ok, monitor renal function]           │
│                                                            │
│ [Complete Verification] [Generate Recommendations]         │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

#### Screen 5: Recommendation Communication
```
┌─────────────────────────────────────────────────────────┐
│ PHARMACIST RECOMMENDATIONS                             │
├─────────────────────────────────────────────────────────┤
│ Patient: Ahmad Bin Ali (ID: 001)                        │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Recommendations (3 items):                              │
│                                                         │
│ 1. ⛔ [URGENT] ADD Lisinopril 10mg PO OD              │
│    ├─ Reason: Medication omission, continues CKD     │
│    │          renoprotection                          │
│    ├─ Rationale: Patient was taking at home,          │
│    │             omitted from hospital orders.        │
│    │             Essential for renal protection.      │
│    ├─ Priority: URGENT (before patient takes others) │
│    └─ Action: [⊙ Communicate to Dr. Ramli]           │
│                                                         │
│ 2. 🟠 [HIGH] Aspirin dose increase: verify rationale  │
│    ├─ Current BPMH: 75mg OD                            │
│    ├─ Current Order: 100mg OD (dose increased)        │
│    ├─ Reason: Verify clinical indication for increase│
│    ├─ Priority: HIGH (before discharge)               │
│    └─ Action: [⊙ Communicate to Physician]            │
│                                                         │
│ 3. 🟡 [ROUTINE] Counsel re: Aspirin + Clopidogrel   │
│    ├─ Clinical Note: Monitor for bleeding signs      │
│    ├─ Patient Education: Provided                      │
│    ├─ Priority: ROUTINE (document and counseled)      │
│    └─ Action: [✓ Documented]                          │
│                                                         │
│ ─────────────────────────────────────────────────      │
│ Communicate to Prescriber:                              │
│ ┌──────────────────────────────────────────────────┐   │
│ │ [✓] Dr. Ramli (Attending)                        │   │
│ │ Method: [○ Paper Note ● Phone Call ○ In-system] │   │
│ │ Date/Time: [06/25/2026 14:30]                    │   │
│ │ Status: [⊙ Awaiting Response] □ Accepted        │   │
│ │         □ Rejected □ Modified                     │   │
│ │                                                    │   │
│ │ Communication Notes:                               │   │
│ │ [Dr. Ramli agreed to ADD Lisinopril back...]     │   │
│ │                                                    │   │
│ │ [SAVE] [CANCEL]                                   │   │
│ └──────────────────────────────────────────────────┘   │
│                                                         │
│ Recommendation Status:                                  │
│ ├─ Pending: 0                                          │
│ ├─ Communicated: 2                                     │
│ ├─ Accepted: 2                                         │
│ └─ Closed: 3 (completed today)                        │
│                                                         │
│ [Previous] [Final Medication List] [Discharge Doc]     │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

#### Screen 6: Analytics Dashboard
```
┌──────────────────────────────────────────────────────────┐
│ ANALYTICS & QUALITY METRICS                             │
├──────────────────────────────────────────────────────────┤
│ Period: June 2026  |  Ward: [All ▼]  |  [Export PDF]   │
├──────────────────────────────────────────────────────────┤
│                                                          │
│ SAFETY METRICS                                           │
│ ┌─────────────┬──────────┬────────┬─────────────┐       │
│ │ Metric      │ This Mo. │ Target │ Status      │       │
│ ├─────────────┼──────────┼────────┼─────────────┤       │
│ │ Admissions  │ 245      │ ----   │ ✓           │       │
│ │ Reconciled  │ 98%      │ 95%    │ ✓ Exceeds   │       │
│ │             │          │        │             │       │
│ │ UMDs Found  │ 82       │ ---    │ ℹ️ Info     │       │
│ │ Rate        │ 33.5/100 │ 30-50  │ ✓ Within RG │       │
│ │             │          │        │             │       │
│ │ Critical    │ 8        │ <5     │ ⚠️ Over     │       │
│ │ Discrepanc. │ (9.8%)   │        │ Target      │       │
│ └─────────────┴──────────┴────────┴─────────────┘       │
│                                                          │
│ TREND ANALYSIS                                           │
│ ┌────────────────────────────────────────────────────┐   │
│ │  Unintended Discrepancies Trend (Last 6 Months)  │   │
│ │                                                    │   │
│ │  35 ┤         ╱╲                                   │   │
│ │  30 ┤    ╱───╱  ╲──╲                              │   │
│ │  25 ┤   ╱        ╲   ╲___                          │   │
│ │  20 ┤  ╱                                            │   │
│ │     └──┴──┴──┴──┴──┴──┴── -> Improving trend!     │   │
│ │      J  F  M  A  M  J                              │   │
│ └────────────────────────────────────────────────────┘   │
│                                                          │
│ TOP MEDICATION CLASSES WITH DISCREPANCIES              │
│ ┌────────────────────────────┐                         │
│ │ ACE Inhibitors    ████ 12  │                         │
│ │ Antidiabetics     ███  10  │                         │
│ │ NSAIDs            ███   9  │                         │
│ │ Statins           ██   7   │                         │
│ │ Anticoagulants    ██   6   │                         │
│ └────────────────────────────┘                         │
│                                                          │
│ [View Details] [Export Report] [Print]                  │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

---

## 10. PERFORMANCE & SCALABILITY SPECIFICATIONS

### 10.1 Performance Targets

| Metric | Target | Rationale |
|--------|--------|-----------|
| Page Load | <2 sec (95th) | User perception threshold |
| Search | <2 sec (95th) | Patient lookup must be fast |
| Data Save | <1 sec (95th) | Smooth user experience |
| Discrepancy Detection | <5 sec | Real-time algorithm execution |
| Report Generation | <60 sec | Complex aggregations acceptable |
| Database Query | <100 ms (95th) | Optimized indexes essential |

### 10.2 Capacity Planning

**Prototype Phase:**
- Concurrent Users: 10-20
- Daily Admissions: 20-30
- Total Database Size: <1 GB
- Backup Storage: Local/Offsite

**Pilot Phase:**
- Concurrent Users: 30-50
- Daily Admissions: 80-100
- Total Database Size: 5-10 GB
- Backup Storage: Redundant, offsite

**Full Implementation (Future):**
- Concurrent Users: 100+
- Daily Admissions: 200-350
- Total Database Size: 50+ GB
- Backup Storage: Enterprise-grade

---

## 11. TESTING REQUIREMENTS

### 11.1 Testing Strategy

**Unit Testing:**
- Minimum 80% code coverage
- Test each service function
- Mock dependencies
- Test error conditions

**Integration Testing:**
- Database integration tests
- Service-to-service interactions
- API endpoint testing
- Data consistency validation

**System Testing:**
- End-to-end workflows
- User scenario testing
- All modules together
- Performance under load

**User Acceptance Testing (UAT):**
- Real users from target roles
- Real workflows from pilot ward
- Feedback on usability
- Sign-off on functionality

---

## 12. DEPLOYMENT & OPERATIONS

### 12.1 Deployment Strategy

**Development Environment:**
- Local development workstations
- Git repository for version control
- Automated testing on commit

**Testing/Staging Environment:**
- Mirror of production (subset of data)
- Testing team access
- Performance testing capability
- Backup recovery testing

**Production Environment (Prototype/Pilot):**
- Server infrastructure (on-premises or cloud)
- Database with redundancy
- Backup and recovery systems
- Monitoring and alerting

### 12.2 Maintenance & Support

**Ongoing Maintenance:**
- Regular database backups
- Security patches
- Performance optimization
- Feature enhancements based on feedback

**Support Structure:**
- First-line support: Pharmacy staff
- IT support: System administrator
- Escalation: Developer/System analyst
- 24/7 critical issue hotline (if needed)

---

## ACCEPTANCE CRITERIA

**System shall be considered ready for deployment when:**

1. ✓ All critical functional requirements implemented
2. ✓ All non-functional requirements met or documented
3. ✓ Unit tests passing (>80% coverage)
4. ✓ Integration tests passing
5. ✓ UAT completed with >80% sign-off
6. ✓ System Usability Scale score ≥68
7. ✓ Performance tests passing (meets response time targets)
8. ✓ Security assessment completed and no critical issues
9. ✓ Documentation complete and reviewed
10. ✓ Training materials completed and staff trained
11. ✓ Go-live readiness review approved by stakeholders
12. ✓ Rollback procedures documented and tested

---

## GLOSSARY

**BPMH:** Best Possible Medication History - comprehensive medication list compiled from all available sources

**UMD:** Unintended Medication Discrepancy - unexplained difference between medication lists

**CDS:** Clinical Decision Support - system providing alerts and recommendations for clinical decisions

**EHR:** Electronic Health Record - comprehensive electronic patient medical record

**PhIS:** Pharmacy Information System - existing system at SASMEC for medication records

**RBAC:** Role-Based Access Control - security model based on user roles

**ORM:** Object-Relational Mapping - software design pattern for database access

---

**Document End**

**Status:** Requirements Complete  
**Next Phase:** System Design & Architecture Specification  
**Approval Required:** By Pharmacy Director & IT Director before development commences

