import 'package:eschool/data/models/subject.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class SubjectPickerBottomsheetContainer extends StatelessWidget {
  const SubjectPickerBottomsheetContainer({
    super.key,
    required this.subjects,
    this.selectedSubject,
  });

  final List<Subject> subjects;
  final Subject? selectedSubject;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(UiUtils.bottomSheetTopRadius),
          topRight: Radius.circular(UiUtils.bottomSheetTopRadius),
        ),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.7,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          BottomSheetTopBarMenu(
            onTapCloseButton: () => Navigator.pop(context),
            padding: EdgeInsets.only(
              top: UiUtils.bottomSheetHorizontalContentPadding,
              left: UiUtils.bottomSheetHorizontalContentPadding,
              right: UiUtils.bottomSheetHorizontalContentPadding,
            ),
            title: UiUtils.getTranslatedLabel(context, filterBySubjectKey),
          ),
          Flexible(
            child: ListView.builder(
              shrinkWrap: true,
              padding: EdgeInsets.zero,
              itemCount: subjects.length + 1, // +1 for "All Subjects"
              itemBuilder: (context, index) {
                final bool isAllSubjects = index == 0;
                final bool isSelected = isAllSubjects
                    ? selectedSubject == null
                    : subjects[index - 1].id == selectedSubject?.id;
                final String label = isAllSubjects
                    ? UiUtils.getTranslatedLabel(context, allSubjectsKey)
                    : (subjects[index - 1].showType
                          ? subjects[index - 1].subjectNameWithType
                          : subjects[index - 1].name);

                return Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: () => Navigator.pop(
                      context,
                      isAllSubjects ? null : subjects[index - 1],
                    ),
                    child: Padding(
                      padding: EdgeInsets.symmetric(
                        vertical: 10,
                        horizontal: UiUtils.bottomSheetHorizontalContentPadding,
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              label,
                              style: TextStyle(
                                fontSize: 16,
                                color: Theme.of(context).colorScheme.onSurface,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          CustomRadioContainer(isSelected: isSelected),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
