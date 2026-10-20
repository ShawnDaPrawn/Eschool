enum StudyMaterialType { file, youtubeVideo, uploadedVideoUrl, other }

StudyMaterialType getStudyMaterialType(int type) {
  if (type == 1) {
    return StudyMaterialType.file;
  }
  if (type == 2) {
    return StudyMaterialType.youtubeVideo;
  }
  if (type == 3) {
    return StudyMaterialType.uploadedVideoUrl;
  }

  return StudyMaterialType.other;
}

class StudyMaterial {

  StudyMaterial({
    required this.studyMaterialType,
    required this.id,
    required this.fileName,
    required this.fileThumbnail,
    required this.fileUrl,
    required this.fileExtension,
  });

  StudyMaterial.fromJson(Map<String, dynamic> json) {
    studyMaterialType = getStudyMaterialType(int.parse(json['type'] ?? '0'));

    id = json['id'] ?? 0;
    fileName = json['file_name'] ?? '';
    fileThumbnail = json['file_thumbnail'] ?? '';
    fileUrl = json['file_url'] ?? '';
    fileExtension = json['file_extension'] ?? '';
  }

  StudyMaterial.fromURL(String url) {
    fileUrl = url;
    fileExtension = url.split('.').last;
    studyMaterialType = StudyMaterialType.file;
    id = 0;
    fileName = url.split('/').last;
    fileThumbnail = '';
  }
  late final StudyMaterialType studyMaterialType;

  late final int id;
  late final String fileName;
  late final String fileThumbnail;
  late final String fileUrl;
  late final String fileExtension;
}
