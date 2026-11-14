// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_contributions.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryContributions _$SharedLibraryContributionsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryContributions', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['contributions', 'sharing']);
  final val = SharedLibraryContributions(
    contributions: $checkedConvert(
      'contributions',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                SharedLibraryContribution.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    sharing: $checkedConvert(
      'sharing',
      (v) =>
          SharedLibraryContributionsSharing.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryContributionsToJson(
  SharedLibraryContributions instance,
) => <String, dynamic>{
  'contributions': instance.contributions.map((e) => e.toJson()).toList(),
  'sharing': instance.sharing.toJson(),
};
