import 'package:eschool/app/routes.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/ui/widgets/subjectImageContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class StudentSubjectsContainer extends StatelessWidget {
  const StudentSubjectsContainer({
    required this.subjects,
    required this.subjectsTitleKey,
    super.key,
    this.childId,
    this.animate = true,
  });
  final String subjectsTitleKey;
  final List<Subject> subjects;
  final int? childId;
  final bool animate;

  final double singleSubjectItemHeight = 150;
  final double singleSubjectItemWidth = 100;

  Widget _buildSubjectContainer({
    required Subject subject,
    required BuildContext context,
  }) {
    return GestureDetector(
      onTap: () {
        Navigator.of(context).pushNamed(
          Routes.subjectDetails,
          arguments: {'childId': childId, 'subject': subject},
        );
      },
      child: SizedBox(
        width: singleSubjectItemWidth,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SubjectImageContainer(
              showShadow: false,
              animate: animate,
              width: singleSubjectItemHeight * .55,
              height: singleSubjectItemHeight * .55,
              radius: 15,
              subject: subject,
            ),
            5.sizedBoxHeight,
            Text(
              subject.showType ? subject.subjectNameWithType : subject.name,
              textAlign: TextAlign.center,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: UiUtils.getColorScheme(context).onSurface,
                fontWeight: FontWeight.w400,
                fontSize: 14,
              ),
            ),
            10.sizedBoxHeight,
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: MediaQuery.sizeOf(context).width,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (subjectsTitleKey.trim().isNotEmpty)
            Padding(
              padding: EdgeInsets.symmetric(
                horizontal:
                    MediaQuery.sizeOf(context).width *
                    UiUtils.screenContentHorizontalPaddingInPercentage,
              ),
              child: Align(
                alignment: AlignmentDirectional.centerStart,
                child: Text(
                  UiUtils.getTranslatedLabel(context, subjectsTitleKey),
                  style: TextStyle(
                    color: UiUtils.getColorScheme(context).onSurface,
                    fontWeight: FontWeight.w600,
                    fontSize: 16,
                  ),
                  textAlign: TextAlign.start,
                ),
              ),
            ),
          if (subjectsTitleKey.trim().isNotEmpty)
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
          SizedBox(
            height: subjects.length <= 4
                ? singleSubjectItemHeight
                : singleSubjectItemHeight * 2,
            width: double.maxFinite,
            child: LayoutBuilder(
              builder: (context, constraints) {
                return ListView.builder(
                  scrollDirection: Axis.horizontal,
                  itemCount: subjects.length <= 4
                      ? subjects.length
                      : (subjects.length / 2).ceil(),
                  itemBuilder: (context, index) {
                    return Padding(
                      padding: EdgeInsetsDirectional.only(
                        start: index == 0
                            ? MediaQuery.sizeOf(context).width *
                                  UiUtils
                                      .screenContentHorizontalPaddingInPercentage
                            : 5,
                        end:
                            (subjects.length <= 4 &&
                                    index == subjects.length - 1) ||
                                (subjects.length > 4 &&
                                    index == ((subjects.length / 2).ceil() - 1))
                            ? MediaQuery.sizeOf(context).width *
                                  UiUtils
                                      .screenContentHorizontalPaddingInPercentage
                            : 5,
                        top: 5,
                        bottom: 5,
                      ),
                      child: subjects.length <= 4
                          ? _buildSubjectContainer(
                              subject: subjects[index],
                              context: context,
                            )
                          : Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Expanded(
                                  child: _buildSubjectContainer(
                                    subject: subjects[index * 2],
                                    context: context,
                                  ),
                                ),
                                if ((index * 2) < subjects.length - 1)
                                  Expanded(
                                    child: _buildSubjectContainer(
                                      subject: subjects[(index * 2) + 1],
                                      context: context,
                                    ),
                                  ),
                              ],
                            ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
