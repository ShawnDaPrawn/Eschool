import 'package:eschool_teacher/data/models/leave.dart';
import 'package:eschool_teacher/ui/screens/leave/widgets/customRadioContainer.dart';
import 'package:eschool_teacher/ui/widgets/bottomSheetTopBarMenu.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class MonthPickerBottomsheetContainer extends StatelessWidget {
  const MonthPickerBottomsheetContainer({
    required this.monthList, required this.selectedMonth, super.key,
  });
  final List<Month> monthList;
  final Month? selectedMonth;

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
        maxHeight: MediaQuery.sizeOf(context).height * 0.7,
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
            title: UiUtils.getTranslatedLabel(context, filterByMonthKey),
          ),
          Flexible(
            child: ListView.builder(
              itemBuilder: (context, index) {
                return Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: () {
                      Navigator.pop(context, monthList[index]);
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
                              UiUtils.getTranslatedLabel(
                                context,
                                monthList[index].nameKey,
                              ),
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
                            isSelected: selectedMonth == monthList[index],
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
              itemCount: monthList.length,
            ),
          ),
        ],
      ),
    );
  }
}
