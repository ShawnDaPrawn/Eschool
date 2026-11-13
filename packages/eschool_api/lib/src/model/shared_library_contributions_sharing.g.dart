// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_contributions_sharing.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryContributionsSharing _$SharedLibraryContributionsSharingFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SharedLibraryContributionsSharing',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'sharing_enabled',
        'leader_approval',
        'library_enabled',
      ],
    );
    final val = SharedLibraryContributionsSharing(
      sharingEnabled: $checkedConvert('sharing_enabled', (v) => v as bool),
      leaderApproval: $checkedConvert('leader_approval', (v) => v as bool),
      libraryEnabled: $checkedConvert('library_enabled', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'sharingEnabled': 'sharing_enabled',
    'leaderApproval': 'leader_approval',
    'libraryEnabled': 'library_enabled',
  },
);

Map<String, dynamic> _$SharedLibraryContributionsSharingToJson(
  SharedLibraryContributionsSharing instance,
) => <String, dynamic>{
  'sharing_enabled': instance.sharingEnabled,
  'leader_approval': instance.leaderApproval,
  'library_enabled': instance.libraryEnabled,
};
