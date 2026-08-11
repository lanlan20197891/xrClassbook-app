// Constellation and category definitions shared across the app

export const CONSTELLATIONS: Record<string, string> = {
  '0': '白羊座', '1': '金牛座', '2': '双子座', '3': '巨蟹座',
  '4': '狮子座', '5': '处女座', '6': '天秤座', '7': '天蝎座',
  '8': '射手座', '9': '摩羯座', '10': '水瓶座', '11': '双鱼座',
};

export const AVATAR_COLORS = [
  '#667eea', '#f5576c', '#4facfe', '#43e97b', '#fa709a',
  '#38f9d7', '#fee140', '#a8edea', '#30cfd0', '#330867',
  '#5ee7df', '#b490ca', '#f093fb', '#4facfe', '#00f2fe',
];

export const CATEGORY_DEFS: Record<string, { label: string; icon: string; color: string }> = {
  close:     { label: '挚友',     icon: '⭐', color: '#f5576c' },
  classmate: { label: '同窗',     icon: '👥', color: '#4facfe' },
  roommate:  { label: '萍水相逢', icon: '🌿', color: '#43e97b' },
  teacher:   { label: '恩师',     icon: '📚', color: '#fee140' },
};

export const RELATION_CATEGORIES = [
  { key: 'close', label: '挚友', icon: '⭐', color: '#f5576c' },
  { key: 'classmate', label: '同窗', icon: '👥', color: '#4facfe' },
  { key: 'roommate', label: '萍水相逢', icon: '🌿', color: '#43e97b' },
  { key: 'teacher', label: '恩师', icon: '📚', color: '#fee140' },
];

export function getCategoryByGroup(group: string): string {
  switch (group) {
    case 'Admin': return 'close';
    case 'Monitor': return 'close';
    default: return 'classmate';
  }
}

export function getGroupLabel(group: string): string {
  switch (group) {
    case 'Admin': return '管理员';
    case 'Monitor': return '班委';
    default: return '同学';
  }
}

export interface ParsedUserData {
  sign: string;
  photo: string;
  qq: string;
  wechat: string;
  birthday: string;
  gender: string;
  motto: string;
  constellation: string;
  hometown: string;
  nowlive: string;
  email: string;
  phone: string;
  like_thing: string;
  dislike_thing: string;
  like_item: string;
  dislike_item: string;
  good_at: string;
}

export function parseUserData(userData: Record<string, any> | null): ParsedUserData {
  const result: ParsedUserData = {
    sign: '这家伙很懒惰，什么都没写！',
    photo: '', qq: '', wechat: '', birthday: '', gender: '',
    motto: '', constellation: '', hometown: '', nowlive: '',
    email: '', phone: '', like_thing: '', dislike_thing: '',
    like_item: '', dislike_item: '', good_at: '',
  };

  if (!userData) return result;

  if (userData.Public) {
    if (userData.Public.Sign) result.sign = userData.Public.Sign;
    if (userData.Public.Photo) result.photo = userData.Public.Photo;
  }
  if (userData.SocialAccount) {
    if (userData.SocialAccount.QQ) result.qq = userData.SocialAccount.QQ;
    if (userData.SocialAccount.WeChat) result.wechat = userData.SocialAccount.WeChat;
  }
  if (userData.MyInfo) {
    const m = userData.MyInfo;
    if (m.Birthday) result.birthday = m.Birthday;
    if (m.Gender !== undefined) result.gender = m.Gender;
    if (m.Motto) result.motto = m.Motto;
    if (m.Constellation !== undefined) result.constellation = m.Constellation;
  }
  if (userData.Location) {
    if (userData.Location.Hometown) result.hometown = userData.Location.Hometown;
    if (userData.Location.NowLive) result.nowlive = userData.Location.NowLive;
  }
  if (userData.ContactMe) {
    if (userData.ContactMe.Email) result.email = userData.ContactMe.Email;
    if (userData.ContactMe.Phone) result.phone = userData.ContactMe.Phone;
  }
  if (userData.LikeAndDislike) {
    const lad = userData.LikeAndDislike;
    if (lad.MyLikeThing) result.like_thing = lad.MyLikeThing;
    if (lad.MyDislikeThing) result.dislike_thing = lad.MyDislikeThing;
    if (lad.MyLikeItem) result.like_item = lad.MyLikeItem;
    if (lad.MyDislikeItem) result.dislike_item = lad.MyDislikeItem;
    if (lad.BeGoodAt) result.good_at = lad.BeGoodAt;
  }

  return result;
}
