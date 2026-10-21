import 'package:eschool/cubits/semestersCubit.dart';
import 'package:eschool/data/models/semester.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class SemesterFilterBottomsheetContainer extends StatefulWidget {
  final Semester selectedSemester;
  const SemesterFilterBottomsheetContainer({
    super.key,
    required this.selectedSemester,
  });

  @override
  State<SemesterFilterBottomsheetContainer> createState() =>
      _SemesterFilterBottomsheetContainerState();
}

class _SemesterFilterBottomsheetContainerState
    extends State<SemesterFilterBottomsheetContainer> {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
        vertical: MediaQuery.sizeOf(context).height * 0.05,
      ),
      width: MediaQuery.sizeOf(context).width,
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(UiUtils.bottomSheetTopRadius),
          topRight: Radius.circular(UiUtils.bottomSheetTopRadius),
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, sortByKey),
            style: TextStyle(
              fontSize: 16,
              color: Theme.of(context).colorScheme.secondary,
              fontWeight: FontWeight.bold,
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Divider(color: Theme.of(context).colorScheme.onSurface),
          ),
          BlocBuilder<SemestersCubit, SemesterState>(
            builder: (context, state) {
              if (state is SemesterFetchSuccess) {
                return RadioGroup<Semester>(
                  groupValue: widget.selectedSemester,
                  onChanged: (value) {
                    Navigator.pop(context, value);
                  },
                  child: ListView.builder(
                    shrinkWrap: true,
                    itemCount: state.semesters.length,
                    padding: EdgeInsets.zero,
                    itemBuilder: (context, index) {
                      final semester = state.semesters[index];
                      return RadioListTile<Semester>(
                        title: Text(
                          semester.name ?? "",
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.secondary,
                            fontSize: 16,
                          ),
                        ),
                        value: semester,
                        controlAffinity: ListTileControlAffinity.trailing,
                      );
                    },
                  ),
                );
              } else if (state is SemesterFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: () {
                      context.read<SemestersCubit>().fetchSemesters();
                    },
                  ),
                );
              } else {
                return const Center(child: CustomCircularProgressIndicator());
              }
            },
          ),
        ],
      ),
    );
  }
}
