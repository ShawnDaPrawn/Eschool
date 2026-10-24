import 'package:eschool_teacher/app/routeArguments.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/subjectsOfClassSectionCubit.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/ui/screens/class/widgets/subjectImageContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class SubjectsContainer extends StatelessWidget {
  const SubjectsContainer({required this.classSectionDetails, super.key});
  final ClassSectionDetails classSectionDetails;

  Widget _buildSubjectContainer({
    required Subject subject,
    required BuildContext context,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: () {
          Navigator.of(context).pushNamed(
            Routes.subject,
            arguments: SubjectScreenArguments(
              subject: subject,
              classSectionDetails: classSectionDetails,
            ),
          );
        },
        child: Container(
          decoration: BoxDecoration(
            boxShadow: [
              BoxShadow(
                color: Theme.of(context).colorScheme.shadow
                    .withValues(alpha: 0.05),
                offset: const Offset(2.5, 2.5),
                blurRadius: 10,
              ),
            ],
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(10),
          ),
          height: 80,
          width: MediaQuery.sizeOf(context).width * 0.85,
          padding: const EdgeInsets.symmetric(horizontal: 12.5, vertical: 10),
          child: LayoutBuilder(
            builder: (context, boxConstraints) {
              return Row(
                children: [
                  SubjectImageContainer(
                    showShadow: false,
                    height: 60,
                    radius: 7.5,
                    subject: subject,
                    width: boxConstraints.maxWidth * 0.2,
                  ),
                  SizedBox(width: boxConstraints.maxWidth * 0.05),
                  Expanded(
                    child: Text(
                      subject.subjectNameWithTypeAndTerm,
                      overflow: TextOverflow.ellipsis,
                      maxLines: 1,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurface,
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
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

  Widget _buildSubjectShimmerLoading(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      width: MediaQuery.sizeOf(context).width * 0.85,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Row(
            children: [
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  margin: const EdgeInsetsDirectional.only(start: 10),
                  height: 60,
                  width: boxConstraints.maxWidth * 0.2,
                ),
              ),
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  margin: const EdgeInsetsDirectional.only(start: 20),
                  width: boxConstraints.maxWidth * 0.3,
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<
      SubjectsOfClassSectionCubit,
      SubjectsOfClassSectionState
    >(
      builder: (context, state) {
        if (state is SubjectsOfClassSectionFetchSuccess) {
          return state.subjects.isEmpty
              ? const NoDataContainer(titleKey: noSubjectsInClassKey)
              : Column(
                  children: List.generate(
                    state.subjects.length,
                    (index) => Animate(
                      effects: listItemAppearanceEffects(
                        itemIndex: index,
                        totalLoadedItems: state.subjects.length,
                      ),
                      child: _buildSubjectContainer(
                        subject: state.subjects[index],
                        context: context,
                      ),
                    ),
                  ),
                );
        }
        if (state is SubjectsOfClassSectionFetchFailure) {
          return Center(
            child: ErrorContainer(
              errorMessageCode: state.errorMessage,
              onTapRetry: () {
                context.read<SubjectsOfClassSectionCubit>().fetchSubjects(
                  classSectionDetails.id,
                );
              },
            ),
          );
        }

        return Column(
          children: List.generate(
            UiUtils.defaultShimmerLoadingContentCount,
            (index) => index,
          ).map((e) => _buildSubjectShimmerLoading(context)).toList(),
        );
      },
    );
  }
}
