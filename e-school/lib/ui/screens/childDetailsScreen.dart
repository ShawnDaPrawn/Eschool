import 'package:eschool/app/selectedChild.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/studentSubjectsCubit.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/ui/widgets/borderedProfilePictureContainer.dart';
import 'package:eschool/ui/widgets/childInformationItemsContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/shimmerLoaders/informationMenuShimmerLoading.dart';
import 'package:eschool/ui/widgets/shimmerLoaders/subjectsShimmerLoadingContainer.dart';
import 'package:eschool/ui/widgets/studentSubjectsContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ChildDetailsScreen extends StatefulWidget {
  const ChildDetailsScreen({required this.student, super.key});
  final Student student;

  @override
  State<ChildDetailsScreen> createState() => _ChildDetailsScreenState();

  /// The child picked in the child switcher, with what a parent can open
  /// about them.
  static Route route(RouteSettings routeSettings) {
    return childRoute(
      (context, child) =>
          withChild(child, (child) => ChildDetailsScreen(student: child)),
    );
  }
}

class _ChildDetailsScreenState extends State<ChildDetailsScreen> {
  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      if (mounted) {
        context.read<ChildSubjectsCubit>().ensureFor(widget.student.id);
      }
    });
    super.initState();
  }

  Widget _buildAppBar() {
    return Align(
      alignment: Alignment.topCenter,
      child: ScreenTopBackgroundContainer(
        padding: EdgeInsets.zero,
        heightPercentage: UiUtils.appBarBiggerHeightPercentage,
        child: LayoutBuilder(
          builder: (context, boxConstraints) {
            return Stack(
              children: [
                //Bordered circles
                PositionedDirectional(
                  top: MediaQuery.sizeOf(context).width * (-0.15),
                  start: MediaQuery.sizeOf(context).width * (-0.225),
                  child: Container(
                    padding: const EdgeInsetsDirectional.only(
                      end: 20,
                      bottom: 20,
                    ),
                    decoration: BoxDecoration(
                      border: Border.all(
                        color: Theme.of(context).colorScheme.onInverseSurface
                            .withValues(alpha: 0.1),
                      ),
                      shape: BoxShape.circle,
                    ),
                    width: MediaQuery.sizeOf(context).width * 0.6,
                    height: MediaQuery.sizeOf(context).width * 0.6,
                    child: Container(
                      decoration: BoxDecoration(
                        border: Border.all(
                          color: Theme.of(context).colorScheme.onInverseSurface
                              .withValues(alpha: 0.1),
                        ),
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
                ),

                //bottom fill circle
                PositionedDirectional(
                  bottom: MediaQuery.sizeOf(context).width * (-0.15),
                  end: MediaQuery.sizeOf(context).width * (-0.15),
                  child: Container(
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.onInverseSurface
                          .withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    width: MediaQuery.sizeOf(context).width * 0.4,
                    height: MediaQuery.sizeOf(context).width * 0.4,
                  ),
                ),
                CustomBackButton(
                  topPadding:
                      MediaQuery.of(context).padding.top +
                      UiUtils.appBarContentTopPadding,
                ),
                Align(
                  alignment: AlignmentDirectional.topCenter,
                  child: Padding(
                    padding: EdgeInsets.only(
                      top:
                          MediaQuery.of(context).padding.top +
                          UiUtils.appBarContentTopPadding,
                      left: 10,
                      right: 10,
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        BorderedProfilePictureContainer(
                          onTap: () {
                            Navigator.of(context).pushNamed(
                              Routes.studentProfile,
                              arguments: widget.student,
                            );
                          },
                          heightAndWidthPercentage: 0.16,
                          boxConstraints: boxConstraints,
                          imageUrl: widget.student.image,
                        ),
                        SizedBox(height: boxConstraints.maxHeight * 0.045),
                        Text(
                          widget.student.getFullName(),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context)
                                .colorScheme
                                .onInverseSurface,
                            fontWeight: FontWeight.w500,
                            fontSize: 15,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        SizedBox(height: boxConstraints.maxHeight * 0.0125),
                        Text(
                          '${UiUtils.getTranslatedLabel(context, classKey)} - ${widget.student.classSectionNameWithTerm(context: context)}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context)
                                .colorScheme
                                .onInverseSurface,
                            fontWeight: FontWeight.w500,
                            fontSize: 11,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildSubjectsAndInformationsContainer() {
    return SingleChildScrollView(
      padding: EdgeInsets.only(
        top: UiUtils.getScrollViewTopPadding(
          context: context,
          appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
        ),
      ),
      child: BlocBuilder<ChildSubjectsCubit, ChildSubjectsState>(
        builder: (context, state) {
          if (state is ChildSubjectsFetchSuccess) {
            final subjects = context.read<ChildSubjectsCubit>().getSubjects();
            return Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (subjects.isNotEmpty) ...[
                  StudentSubjectsContainer(
                    subjects: subjects,
                    subjectsTitleKey: subjectsKey,
                    childId: widget.student.id,
                  ),
                  SizedBox(height: MediaQuery.sizeOf(context).height * 0.02),
                ],
                ChildInformationItemsContainer(
                  student: widget.student,
                  subjectsForFilter: context
                      .read<ChildSubjectsCubit>()
                      .getSubjectsForAssignmentContainer(),
                ),
                SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
              ],
            );
          }
          if (state is ChildSubjectsFetchFailure) {
            return Center(
              child: ErrorContainer(
                errorMessageCode: state.errorMessage,
                onTapRetry: () {
                  context.read<ChildSubjectsCubit>().fetchChildSubjects(
                    widget.student.id,
                  );
                },
              ),
            );
          }
          return Column(
            children: [
              SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
              const SubjectsShimmerLoadingContainer(),
              SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
              ...List.generate(
                UiUtils.defaultShimmerLoadingContentCount,
                (index) => index,
              ).map((e) => const InformationMenuShimmerContainer()),
            ],
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [_buildSubjectsAndInformationsContainer(), _buildAppBar()],
      ),
    );
  }
}
