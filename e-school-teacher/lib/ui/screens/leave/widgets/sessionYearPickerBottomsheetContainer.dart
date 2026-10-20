import 'package:eschool_teacher/data/models/sessionYear.dart';
import 'package:eschool_teacher/ui/screens/leave/widgets/customRadioContainer.dart';
import 'package:eschool_teacher/ui/widgets/bottomSheetTopBarMenu.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class SessionYearPickerBottomsheetContainer extends StatelessWidget {
  const SessionYearPickerBottomsheetContainer({
    required this.sessionYearList, required this.selectedSessionYear, super.key,
  });
  final List<SessionYear> sessionYearList;
  final SessionYear selectedSessionYear;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(UiUtils.bottomSheetTopRadius),
          topRight: Radius.circular(UiUtils.bottomSheetTopRadius),
        ),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.5,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          BottomSheetTopBarMenu(
            onTapCloseButton: () {
              Navigator.pop(context);
            },
            padding: EdgeInsets.only(
              top: UiUtils.bottomSheetHorizontalContentPadding,
              left: UiUtils.bottomSheetHorizontalContentPadding,
              right: UiUtils.bottomSheetHorizontalContentPadding,
            ),
            title: UiUtils.getTranslatedLabel(context, filterBySessionYearKey),
          ),
          Flexible(
            child: ListView.builder(
              itemBuilder: (context, index) {
                return Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: () {
                      Navigator.pop(context, sessionYearList[index]);
                    },
                    child: Padding(
                      padding: EdgeInsets.symmetric(
                        vertical: 10,
                        horizontal: UiUtils.bottomSheetHorizontalContentPadding,
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              sessionYearList[index].name,
                              textAlign: TextAlign.start,
                              style: TextStyle(
                                fontSize: 16,
                                color: Theme.of(context).colorScheme.secondary,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          CustomRadioContainer(
                            isSelected:
                                selectedSessionYear == sessionYearList[index],
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
              itemCount: sessionYearList.length,
            ),
          ),
        ],
      ),
    );
  }
}
