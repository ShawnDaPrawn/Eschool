import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/student.dart';
import 'package:eschool_teacher/ui/widgets/customUserProfileImageWidget.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class StudentTileContainer extends StatelessWidget {
  const StudentTileContainer({required this.student, super.key});
  final Student student;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: () {
          Navigator.of(
            context,
          ).pushNamed(Routes.studentDetails, arguments: student);
        },
        child: Container(
          decoration: BoxDecoration(
            boxShadow: [
              BoxShadow(
                color: Theme.of(
                  context,
                ).colorScheme.secondary.withValues(alpha: 0.05),
                offset: const Offset(2.5, 2.5),
                blurRadius: 10,
                spreadRadius: 2.5,
              ),
            ],
            color: Theme.of(context).scaffoldBackgroundColor,
            borderRadius: BorderRadius.circular(10),
          ),
          width: MediaQuery.sizeOf(context).width * 0.85,
          padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 12.5),
          child: LayoutBuilder(
            builder: (context, boxConstraints) {
              return Row(
                children: [
                  Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(7.5),
                      color: Theme.of(context).colorScheme.error,
                    ),
                    height: 50,
                    width: boxConstraints.maxWidth * 0.2,
                    child: CustomUserProfileImageWidget(
                      profileUrl: student.image,
                      radius: BorderRadius.circular(7.5),
                    ),
                  ),
                  SizedBox(width: boxConstraints.maxWidth * 0.05),
                  SizedBox(
                    width: boxConstraints.maxWidth * 0.65,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          student.getFullName(),
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.secondary,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                        ),
                        Text(
                          '${UiUtils.getTranslatedLabel(context, rollNoKey)} - ${student.rollNumber}',
                          style: TextStyle(
                            color: Theme.of(
                              context,
                            ).colorScheme.secondary.withValues(alpha: 0.75),
                            fontWeight: FontWeight.w400,
                            fontSize: 12.5,
                          ),
                          textAlign: TextAlign.start,
                        ),
                      ],
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}
