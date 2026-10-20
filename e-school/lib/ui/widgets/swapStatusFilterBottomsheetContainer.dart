import 'package:eschool/ui/screens/leave/widgets/customRadioContainer.dart';
import 'package:eschool/ui/widgets/bottomSheetTopBarMenu.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class SwapStatusFilterBottomsheetContainer extends StatelessWidget {
  const SwapStatusFilterBottomsheetContainer({
    required this.statusList, required this.selectedStatus, super.key,
  });
  final List<String> statusList;
  final String selectedStatus;

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
        maxHeight: MediaQuery.sizeOf(context).height * 0.35,
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
            title: UiUtils.getTranslatedLabel(context, filterByStatusKey),
          ),
          Flexible(
            child: ListView.builder(
              itemBuilder: (context, index) {
                return Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: () {
                      Navigator.pop(context, statusList[index]);
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
                                statusList[index],
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
                            isSelected: selectedStatus == statusList[index],
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
              itemCount: statusList.length,
            ),
          ),
        ],
      ),
    );
  }
}
