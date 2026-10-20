import 'package:eschool/app/routes.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_svg/flutter_svg.dart';

class ChildInformationItemsContainer extends StatefulWidget {
  const ChildInformationItemsContainer({
    required this.student,
    required this.subjectsForFilter,
    super.key,
  });
  final Student student;
  final List<Subject> subjectsForFilter;

  @override
  ChildInformationItemsContainerState createState() =>
      ChildInformationItemsContainerState();
}

class ChildInformationItemsContainerState
    extends State<ChildInformationItemsContainer> {
  late final List<MenuContainerDetails> _menuItems = [
    MenuContainerDetails(
      route: Routes.childAssignments,
      arguments: {
        'childId': widget.student.id,
        'subjects': widget.subjectsForFilter,
      },
      iconPath: Assets.assignmentIconParent,
      titleKey: assignmentsKey,
    ),
    MenuContainerDetails(
      route: Routes.childTeachers,
      arguments: widget.student.id,
      iconPath: Assets.teachersIcon,
      titleKey: teachersKey,
    ),
    MenuContainerDetails(
      route: Routes.childAttendance,
      arguments: widget.student.id,
      iconPath: Assets.attendanceIcon,
      titleKey: attendanceKey,
    ),
    MenuContainerDetails(
      route: Routes.childTimeTable,
      arguments: widget.student.id,
      iconPath: Assets.timetableIcon,
      titleKey: timeTableKey,
    ),
    MenuContainerDetails(
      route: Routes.academicCalendar,
      arguments: {'hasBack': true, 'childId': widget.student.id},
      iconPath: Assets.holidayIcon,
      titleKey: academicCalendarKey,
    ),
    MenuContainerDetails(
      route: Routes.exam,
      arguments: {
        'childId': widget.student.id,
        'subjects': widget.subjectsForFilter,
      },
      iconPath: Assets.examIcon,
      titleKey: examsKey,
    ),
    MenuContainerDetails(
      route: Routes.childResults,
      arguments: {
        'childId': widget.student.id,
        'subjects': widget.subjectsForFilter,
      },
      iconPath: Assets.resultIcon,
      titleKey: resultKey,
    ),
    MenuContainerDetails(
      route: Routes.subjectWiseReport,
      arguments: {
        'childId': widget.student.id,
        'subjects': widget.subjectsForFilter,
      },
      iconPath: Assets.reportsIcon,
      titleKey: reportsKey,
    ),
    MenuContainerDetails(
      route: Routes.childFees,
      arguments: {'studentDetails': widget.student},
      iconPath: Assets.feesIcon,
      titleKey: feesKey,
    ),
    MenuContainerDetails(
      route: Routes.manageLeaves,
      arguments: {'studentDetails': widget.student},
      iconPath: Assets.manageLeavesIcon,
      titleKey: manageLeavesKey,
    ),
  ];

  Widget _buildInformationAndMenu() {
    return SingleChildScrollView(
      padding: EdgeInsets.only(
        left: MediaQuery.sizeOf(context).width * 0.075,
        right: MediaQuery.sizeOf(context).width * 0.075,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, informationKey),
            style: TextStyle(
              color: UiUtils.getColorScheme(context).secondary,
              fontWeight: FontWeight.w600,
              fontSize: 16,
            ),
            textAlign: TextAlign.start,
          ),
          SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: List.generate(
              _menuItems.length,
              (index) => _buildMenuContainer(itemIndex: index),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuContainer({required int itemIndex}) {
    return Animate(
      effects: listItemAppearanceEffects(
        itemIndex: itemIndex,
        totalLoadedItems: _menuItems.length,
      ),
      child: Padding(
        padding: const EdgeInsets.only(bottom: 15),
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () {
            Navigator.of(context).pushNamed(
              _menuItems[itemIndex].route,
              arguments: _menuItems[itemIndex].arguments,
            );
          },
          child: Container(
            height: 80,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: Theme.of(
                  context,
                ).colorScheme.secondary.withValues(alpha: 0.25),
              ),
            ),
            child: LayoutBuilder(
              builder: (context, boxConstraints) {
                return Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(14),
                      margin: const EdgeInsets.symmetric(horizontal: 10),
                      height: 60,
                      decoration: BoxDecoration(
                        color: Theme.of(
                          context,
                        ).colorScheme.onSecondary.withValues(alpha: 0.225),
                        borderRadius: BorderRadius.circular(15),
                      ),
                      width: boxConstraints.maxWidth * 0.225,
                      child: SvgPicture.asset(_menuItems[itemIndex].iconPath),
                    ),
                    SizedBox(width: boxConstraints.maxWidth * 0.025),
                    SizedBox(
                      width: boxConstraints.maxWidth * 0.475,
                      child: Text(
                        UiUtils.getTranslatedLabel(
                          context,
                          _menuItems[itemIndex].titleKey,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.secondary,
                          fontWeight: FontWeight.w500,
                          fontSize: 15,
                        ),
                      ),
                    ),
                    const Spacer(),
                    CircleAvatar(
                      backgroundColor: Theme.of(context).colorScheme.secondary,
                      radius: 17.5,
                      child: Icon(
                        Icons.arrow_forward,
                        size: 22.5,
                        color: Theme.of(context).scaffoldBackgroundColor,
                      ),
                    ),
                    SizedBox(width: boxConstraints.maxWidth * 0.035),
                  ],
                );
              },
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return _buildInformationAndMenu();
  }
}

//class to maintain details required by each menu items
class MenuContainerDetails {
  MenuContainerDetails({
    required this.iconPath,
    required this.titleKey,
    required this.route,
    this.arguments,
  });
  String iconPath;
  String titleKey;
  String route;
  Object? arguments;
}
