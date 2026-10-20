import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/data/models/classDetails.dart';
import 'package:eschool_teacher/data/models/sectionDetails.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ClassSectionDetails {

  ClassSectionDetails({
    required this.id,
    required this.classDetails,
    required this.sectionDetails,
  });
  final int id;
  final ClassDetails classDetails;
  final SectionDetails sectionDetails;

  String getClassSectionNameWithSemester({required BuildContext context}) {
    if (context
                .read<AppConfigurationCubit>()
                .getAppConfiguration()
                .currentSemester !=
            null &&
        classDetails.includesSemester) {
      return '${classDetails.name} ${sectionDetails.name} - ${context.read<AppConfigurationCubit>().getAppConfiguration().currentSemester!.name}';
    }
    return '${classDetails.name} - ${sectionDetails.name}';
  }

  String get classSectionName =>
      '${classDetails.name} - ${sectionDetails.name}';

  String getClassSectionNameWithMediumAndSemester({
    required BuildContext context,
  }) {
    return '${getClassSectionNameWithSemester(context: context)} (${classDetails.medium.name})';
  }

  String getClassSectionNameWithMedium() {
    return '${classDetails.name} ${sectionDetails.name} (${classDetails.medium.name})';
  }

  static ClassSectionDetails fromJson(Map<String, dynamic> json) {
    return ClassSectionDetails(
      sectionDetails: SectionDetails.fromJson(
        Map.from(json['section']),
        json['class']['streams'] == null || json['class']['streams'] == ''
            ? null
            : ' ${json['class']['streams']['name']}',
      ),
      classDetails: ClassDetails.fromJson(Map.from(json['class'])),
      id: json['id'] ?? 0,
    );
  }
}
