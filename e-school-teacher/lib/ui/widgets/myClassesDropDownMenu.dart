import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/subjectsOfClassSectionCubit.dart';
import 'package:eschool_teacher/ui/widgets/customDropDownMenu.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class MyClassesDropDownMenu extends StatelessWidget {

  const MyClassesDropDownMenu({
    required this.currentSelectedItem, required this.width, required this.changeSelectedItem, super.key,
  });
  final CustomDropDownItem currentSelectedItem;
  final Function(CustomDropDownItem) changeSelectedItem;
  final double width;

  @override
  Widget build(BuildContext context) {
    return CustomDropDownMenu(
      width: width,
      onChanged: (result) {
        if (result != null && result != currentSelectedItem) {
          changeSelectedItem(result);

          context.read<SubjectsOfClassSectionCubit>().fetchSubjects(
                context
                    .read<DashboardCubit>()
                    .getClassSectionDetails(index: result.index)
                    .id,
              );
        }
      },
      menu: context.read<DashboardCubit>().getClassSectionName(),
      currentSelectedItem: currentSelectedItem,
    );
  }
}
