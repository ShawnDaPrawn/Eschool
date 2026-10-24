---
title: Subject standards
---

Open **Academics → Subject standards** to search published CAPS and IEB reference
revisions by name, code, framework or grade. Every result shows its source,
version and effective date. Mapping a reference does not rename a subject,
change a learner mark or replace an assessment plan.

Under **School subject mappings**, choose the matching reference and save. Choose
**No reference** to clear it. Existing references remain selectable even when
filters change. To add a subject, expand **Create school subject** on a reference,
choose the medium, type and colour, and save. If a matching subject already
exists, map it instead. Subject list/create/edit permissions control these actions.

The platform operator imports reviewed JSON using:

```sh
php artisan academics:import-subject-standards reviewed-catalogue.json
php artisan academics:import-subject-standards reviewed-catalogue.json --publish
```

The first command imports draft revisions; the second publishes them. Repeating
an identical revision is harmless. Changed content needs a new version. Schools
cannot modify the shared catalogue through the API.

A historical public baseline is supplied at
`database/data/dbe-ncs-2015-subject-standards.json`, transcribed from the DBE's
[2015 consolidated NCS regulations](https://www.education.gov.za/LinkClick.aspx?fileticket=eaGB4NP18nw%3D&mid=4018&portalid=0&tabid=419), Annexures A–D. It contains grade-specific
codes, including Grade R, and is explicitly versioned **DBE-NCS-2015**. It is not
a claim that the 2015 list contains all later amendments. Review current DBE/IEB
requirements and import updated revisions before choosing a live school's policy.
The baseline contains no guessed assessment weights. Licensed IEB references
can be imported with the same format and their own source/version.

Assessment component weights, when provided, total 10,000 basis points (100%).
Subject-specific practical/oral components are supported alongside SBA and exams.
The school's published assessment plan determines actual calculations; mapping a
catalogue entry alone does not change results.
